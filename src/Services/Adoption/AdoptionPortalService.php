<?php

declare(strict_types=1);

namespace App\Services\Adoption;

use App\Core\Database;
use App\Core\Request;
use App\Helpers\IdGenerator;
use App\Helpers\Sanitizer;
use App\Models\AdoptionApplication;
use App\Models\Animal;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Support\InputNormalizer;
use App\Support\MediaPath;
use RuntimeException;

class AdoptionPortalService
{
    public function __construct(
        private readonly AdoptionApplication $applications,
        private readonly Animal $animals,
        private readonly User $users,
        private readonly AdoptionReadService $reads,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications
    ) {
    }

    public function featuredAnimals(int $limit = 6): array
    {
        $animals = Database::fetchAll(
            "SELECT a.id, a.animal_id, a.name, a.species, a.gender, a.size, a.age_years, a.age_months,
                    a.temperament, b.name AS breed_name, p.file_path AS primary_photo_path
             FROM animals a
             LEFT JOIN breeds b ON b.id = a.breed_id
             LEFT JOIN animal_photos p ON p.animal_id = a.id AND p.is_primary = 1
             WHERE a.is_deleted = 0
               AND a.status = 'Available'
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT {$limit}"
        );

        foreach ($animals as &$animal) {
            $animal['primary_photo_path'] = MediaPath::normalizePublicImagePath($animal['primary_photo_path'] ?? null);
        }
        unset($animal);

        return $animals;
    }

    public function availableAnimals(array $filters, int $page, int $perPage): array
    {
        $clauses = ["a.is_deleted = 0", "a.status = 'Available'"];
        $bindings = [];
        $offset = ($page - 1) * $perPage;

        if (($filters['search'] ?? '') !== '') {
            $clauses[] = '(a.name LIKE :search OR a.animal_id LIKE :search OR b.name LIKE :search)';
            $bindings['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        foreach (['species', 'gender', 'size'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $clauses[] = "a.{$field} = :{$field}";
                $bindings[$field] = $filters[$field];
            }
        }

        $whereSql = 'WHERE ' . implode(' AND ', $clauses);

        $items = Database::fetchAll(
            "SELECT a.id, a.animal_id, a.name, a.species, a.gender, a.size, a.age_years, a.age_months,
                    a.color_markings, a.temperament, a.condition_at_intake, a.distinguishing_features,
                    b.name AS breed_name, p.file_path AS primary_photo_path
             FROM animals a
             LEFT JOIN breeds b ON b.id = a.breed_id
             LEFT JOIN animal_photos p ON p.animal_id = a.id AND p.is_primary = 1
             {$whereSql}
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM animals a
             LEFT JOIN breeds b ON b.id = a.breed_id
             {$whereSql}",
            $bindings
        );

        foreach ($items as &$item) {
            $item['primary_photo_path'] = MediaPath::normalizePublicImagePath($item['primary_photo_path'] ?? null);
        }
        unset($item);

        return [
            'items' => $items,
            'total' => (int) ($count['aggregate'] ?? 0),
        ];
    }

    public function publicAnimalDetail(int|string $id): array
    {
        $animal = $this->animals->find($id);
        if ($animal === false || (string) $animal['status'] !== 'Available') {
            throw new RuntimeException('Animal not found.');
        }

        $animal['photos'] = MediaPath::filterValidImageRows(Database::fetchAll(
            'SELECT id, file_path, file_name, is_primary
             FROM animal_photos
             WHERE animal_id = :animal_id
             ORDER BY is_primary DESC, sort_order ASC, id ASC',
            ['animal_id' => $animal['id']]
        ));

        return $animal;
    }

    public function registerAdopter(array $data, Request $request): array
    {
        $role = Database::fetch("SELECT id FROM roles WHERE name = 'adopter' LIMIT 1");
        if ($role === false) {
            throw new RuntimeException('Adopter role is not configured.');
        }

        $userId = $this->users->create([
            'role_id' => (int) $role['id'],
            'email' => strtolower(trim((string) $data['email'])),
            'password_hash' => password_hash((string) $data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'middle_name' => InputNormalizer::nullIfBlank($data['middle_name'] ?? null),
            'phone' => Sanitizer::phone($data['phone'] ?? null),
            'address_line1' => trim((string) $data['address_line1']),
            'address_line2' => InputNormalizer::nullIfBlank($data['address_line2'] ?? null),
            'city' => trim((string) $data['city']),
            'province' => trim((string) $data['province']),
            'zip_code' => trim((string) $data['zip_code']),
            'is_active' => 1,
            'email_verified_at' => date('Y-m-d H:i:s'),
            'force_password_change' => 0,
            'created_by' => null,
            'updated_by' => null,
        ]);

        $this->users->assignGeneratedUsername($userId);
        $user = $this->users->findById($userId);
        $this->audit->record(null, 'create', 'adoptions', 'users', $userId, [], $user ?: [], $request);

        if ($user === false) {
            throw new RuntimeException('Adopter account was not created.');
        }

        $this->notifications->notifyRole('super_admin', [
            'type' => 'info',
            'title' => 'New Adopter Registration',
            'message' => 'A new public user (' . ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '') . ') has registered as an adopter.',
            'link' => '/users/' . $userId,
        ]);

        return $user;
    }

    public function submitPortalApplication(int $userId, array $data, array $file, Request $request): array
    {
        $user = $this->users->findById($userId);
        if ($user === false || (string) $user['role_name'] !== 'adopter') {
            throw new RuntimeException('Only adopter accounts can submit adoption applications.');
        }

        $animalId = ($data['animal_id'] ?? '') !== '' ? (int) $data['animal_id'] : null;
        if ($animalId !== null) {
            $animal = $this->animals->find($animalId);
            if ($animal === false || (string) ($animal['status'] ?? '') !== 'Available') {
                throw new RuntimeException('The selected animal is no longer available for adoption.');
            }
        }

        $validIdPath = $this->storePortalDocument($file, 'valid-id');
        $payload = [
            'application_number' => IdGenerator::next('application_number'),
            'adopter_id' => $userId,
            'animal_id' => $animalId,
            'status' => 'pending_review',
            'preferred_species' => InputNormalizer::nullIfBlank($data['preferred_species'] ?? null),
            'preferred_breed' => InputNormalizer::nullIfBlank($data['preferred_breed'] ?? null),
            'preferred_age_min' => ($data['preferred_age_min'] ?? '') !== '' ? (int) $data['preferred_age_min'] : null,
            'preferred_age_max' => ($data['preferred_age_max'] ?? '') !== '' ? (int) $data['preferred_age_max'] : null,
            'preferred_size' => InputNormalizer::nullIfBlank($data['preferred_size'] ?? null),
            'preferred_gender' => InputNormalizer::nullIfBlank($data['preferred_gender'] ?? null),
            'housing_type' => (string) $data['housing_type'],
            'housing_ownership' => (string) $data['housing_ownership'],
            'has_yard' => InputNormalizer::bool($data['has_yard'] ?? false) ? 1 : 0,
            'yard_size' => InputNormalizer::nullIfBlank($data['yard_size'] ?? null),
            'num_adults' => (int) $data['num_adults'],
            'num_children' => (int) $data['num_children'],
            'children_ages' => InputNormalizer::nullIfBlank($data['children_ages'] ?? null),
            'existing_pets_description' => InputNormalizer::nullIfBlank($data['existing_pets_description'] ?? null),
            'previous_pet_experience' => InputNormalizer::nullIfBlank($data['previous_pet_experience'] ?? null),
            'vet_reference_name' => InputNormalizer::nullIfBlank($data['vet_reference_name'] ?? null),
            'vet_reference_clinic' => InputNormalizer::nullIfBlank($data['vet_reference_clinic'] ?? null),
            'vet_reference_contact' => Sanitizer::phone($data['vet_reference_contact'] ?? null),
            'valid_id_path' => $validIdPath,
            'digital_signature_path' => null,
            'agrees_to_policies' => InputNormalizer::bool($data['agrees_to_policies'] ?? false) ? 1 : 0,
            'agrees_to_home_visit' => InputNormalizer::bool($data['agrees_to_home_visit'] ?? false) ? 1 : 0,
            'agrees_to_return_policy' => InputNormalizer::bool($data['agrees_to_return_policy'] ?? false) ? 1 : 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];

        $applicationId = $this->applications->create($payload);
        $application = $this->reads->get($applicationId);
        $this->notifications->create([
            'user_id' => $userId,
            'type' => 'adoption_application',
            'title' => 'Application received',
            'message' => 'Your adoption application ' . $application['application_number'] . ' is now pending review.',
            'link' => '/adopt/apply',
        ]);
        $this->audit->record($userId, 'create', 'adoptions', 'adoption_applications', $applicationId, [], $application, $request);

        $this->notifications->notifyRole('super_admin', [
            'type' => 'info',
            'title' => 'New Adoption Application',
            'message' => 'A new adoption application (' . ($application['application_number'] ?? '') . ') has been submitted and is pending review.',
            'link' => '/adoptions/' . $applicationId,
        ]);

        return $application;
    }

    public function myApplications(int $userId): array
    {
        return Database::fetchAll(
            "SELECT aa.id, aa.application_number, aa.status, aa.created_at, aa.updated_at,
                    aa.rejection_reason, aa.withdrawn_reason,
                    a.animal_id AS animal_code, a.name AS animal_name, a.species AS animal_species
             FROM adoption_applications aa
             LEFT JOIN animals a ON a.id = aa.animal_id
             WHERE aa.adopter_id = :adopter_id
               AND aa.is_deleted = 0
             ORDER BY aa.created_at DESC, aa.id DESC",
            ['adopter_id' => $userId]
        );
    }

    private function storePortalDocument(array $file, string $prefix): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('A valid ID document is required.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $directory = dirname(__DIR__, 3) . '/public/uploads/adoptions/documents';

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to prepare portal document storage.');
        }

        $fileName = $prefix . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
        $absolutePath = $directory . '/' . $fileName;
        $source = (string) ($file['tmp_name'] ?? '');
        $moved = is_uploaded_file($source)
            ? move_uploaded_file($source, $absolutePath)
            : copy($source, $absolutePath);

        if (!$moved) {
            throw new RuntimeException('Failed to store the uploaded ID document.');
        }

        return 'uploads/adoptions/documents/' . $fileName;
    }
}
