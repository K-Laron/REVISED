<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Models\AdoptionApplication;
use App\Models\AdoptionCompletion;
use App\Models\AdoptionInterview;
use App\Models\AdoptionSeminar;
use App\Models\Animal;
use App\Models\User;
use App\Helpers\IdGenerator;
use App\Helpers\Sanitizer;
use App\Support\MediaPath;
use RuntimeException;

class AdoptionService
{
    private const STATUSES = [
        'pending_review' => 'Pending Review',
        'interview_scheduled' => 'Interview Scheduled',
        'interview_completed' => 'Interview Completed',
        'seminar_scheduled' => 'Seminar Scheduled',
        'seminar_completed' => 'Seminar Completed',
        'pending_payment' => 'Pending Payment',
        'completed' => 'Completed',
        'rejected' => 'Rejected',
        'withdrawn' => 'Withdrawn',
    ];

    private const STATUS_FLOW = [
        'pending_review' => ['interview_scheduled', 'rejected', 'withdrawn'],
        'interview_scheduled' => ['interview_completed', 'rejected', 'withdrawn'],
        'interview_completed' => ['seminar_scheduled', 'rejected', 'withdrawn'],
        'seminar_scheduled' => ['seminar_completed', 'pending_payment', 'rejected', 'withdrawn'],
        'seminar_completed' => ['pending_payment', 'completed', 'rejected', 'withdrawn'],
        'pending_payment' => ['completed', 'rejected', 'withdrawn'],
        'completed' => [],
        'rejected' => [],
        'withdrawn' => [],
    ];

    private AdoptionApplication $applications;
    private AdoptionInterview $interviews;
    private AdoptionSeminar $seminars;
    private AdoptionCompletion $completions;
    private Animal $animals;
    private User $users;
    private PdfService $pdfs;
    private AuditService $audit;

    public function __construct()
    {
        $this->applications = new AdoptionApplication();
        $this->interviews = new AdoptionInterview();
        $this->seminars = new AdoptionSeminar();
        $this->completions = new AdoptionCompletion();
        $this->animals = new Animal();
        $this->users = new User();
        $this->pdfs = new PdfService();
        $this->audit = new AuditService();
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        $result = $this->applications->paginate($filters, $page, $perPage);

        foreach ($result['items'] as &$item) {
            $item['billing_summary'] = $this->billingSummary((int) $item['id']);
            $item['days_in_stage'] = $this->daysInStage((string) $item['updated_at']);
        }
        unset($item);

        return $result;
    }

    public function get(int $id): array
    {
        $application = $this->applications->find($id);
        if ($application === false) {
            throw new RuntimeException('Adoption application not found.');
        }

        $application['interviews'] = $this->interviews->listByApplication($id);
        $application['seminars'] = $this->seminars->listByApplication($id);
        $application['completion'] = $this->completions->findByApplication($id) ?: null;
        $application['invoices'] = $this->linkedInvoices($id);
        $application['billing_summary'] = $this->billingSummaryFromInvoices($application['invoices']);
        $application['available_statuses'] = self::STATUS_FLOW[(string) $application['status']] ?? [];
        $application['days_in_stage'] = $this->daysInStage((string) $application['updated_at']);

        return $application;
    }

    public function pipelineStats(): array
    {
        $counts = $this->applications->buildPipelineStats();
        $statuses = [];

        foreach (self::STATUSES as $key => $label) {
            $statuses[] = [
                'key' => $key,
                'label' => $label,
                'count' => (int) ($counts[$key] ?? 0),
            ];
        }

        return [
            'statuses' => $statuses,
            'upcoming_interviews' => (int) (Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM adoption_interviews
                 WHERE status = 'scheduled'
                   AND scheduled_date >= NOW()"
            )['aggregate'] ?? 0),
            'upcoming_seminars' => (int) (Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM adoption_seminars
                 WHERE status IN ('scheduled', 'in_progress')
                   AND scheduled_date >= NOW()"
            )['aggregate'] ?? 0),
            'ready_for_completion' => (int) (Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM adoption_applications
                 WHERE is_deleted = 0
                   AND status IN ('seminar_completed', 'pending_payment')"
            )['aggregate'] ?? 0),
        ];
    }

    public function seminarsList(array $filters = []): array
    {
        $seminars = $this->seminars->list($filters);
        foreach ($seminars as &$seminar) {
            $seminar['attendees'] = $this->seminars->attendees((int) $seminar['id']);
        }
        unset($seminar);

        return $seminars;
    }

    public function staffOptions(): array
    {
        return Database::fetchAll(
            'SELECT u.id,
                    CONCAT(u.first_name, " ", u.last_name) AS full_name,
                    u.email,
                    r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_deleted = 0
               AND u.is_active = 1
             ORDER BY u.first_name ASC, u.last_name ASC'
        );
    }

    public function statusLabels(): array
    {
        return self::STATUSES;
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
            'middle_name' => $this->nullIfBlank($data['middle_name'] ?? null),
            'phone' => Sanitizer::phone($data['phone'] ?? null),
            'address_line1' => trim((string) $data['address_line1']),
            'address_line2' => $this->nullIfBlank($data['address_line2'] ?? null),
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

        (new \App\Services\NotificationService())->notifyRole('super_admin', [
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
            'preferred_species' => $this->nullIfBlank($data['preferred_species'] ?? null),
            'preferred_breed' => $this->nullIfBlank($data['preferred_breed'] ?? null),
            'preferred_age_min' => ($data['preferred_age_min'] ?? '') !== '' ? (int) $data['preferred_age_min'] : null,
            'preferred_age_max' => ($data['preferred_age_max'] ?? '') !== '' ? (int) $data['preferred_age_max'] : null,
            'preferred_size' => $this->nullIfBlank($data['preferred_size'] ?? null),
            'preferred_gender' => $this->nullIfBlank($data['preferred_gender'] ?? null),
            'housing_type' => (string) $data['housing_type'],
            'housing_ownership' => (string) $data['housing_ownership'],
            'has_yard' => $this->toBool($data['has_yard'] ?? false) ? 1 : 0,
            'yard_size' => $this->nullIfBlank($data['yard_size'] ?? null),
            'num_adults' => (int) $data['num_adults'],
            'num_children' => (int) $data['num_children'],
            'children_ages' => $this->nullIfBlank($data['children_ages'] ?? null),
            'existing_pets_description' => $this->nullIfBlank($data['existing_pets_description'] ?? null),
            'previous_pet_experience' => $this->nullIfBlank($data['previous_pet_experience'] ?? null),
            'vet_reference_name' => $this->nullIfBlank($data['vet_reference_name'] ?? null),
            'vet_reference_clinic' => $this->nullIfBlank($data['vet_reference_clinic'] ?? null),
            'vet_reference_contact' => Sanitizer::phone($data['vet_reference_contact'] ?? null),
            'valid_id_path' => $validIdPath,
            'digital_signature_path' => null,
            'agrees_to_policies' => $this->toBool($data['agrees_to_policies'] ?? false) ? 1 : 0,
            'agrees_to_home_visit' => $this->toBool($data['agrees_to_home_visit'] ?? false) ? 1 : 0,
            'agrees_to_return_policy' => $this->toBool($data['agrees_to_return_policy'] ?? false) ? 1 : 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ];

        $applicationId = $this->applications->create($payload);
        $application = $this->get($applicationId);
        $this->createNotification(
            $userId,
            'adoption_application',
            'Application received',
            'Your adoption application ' . $application['application_number'] . ' is now pending review.',
            '/adopt/apply'
        );
        $this->audit->record($userId, 'create', 'adoptions', 'adoption_applications', $applicationId, [], $application, $request);

        (new \App\Services\NotificationService())->notifyRole('super_admin', [
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

    public function updateStatus(int $applicationId, string $status, int $userId, Request $request): array
    {
        $current = $this->get($applicationId);
        $this->assertTransition((string) $current['status'], $status);
        $this->applications->updateStatus($applicationId, $status, null, null, $userId);
        $updated = $this->get($applicationId);
        $this->audit->record($userId, 'update', 'adoptions', 'adoption_applications', $applicationId, $current, $updated, $request);

        return $updated;
    }

    public function reject(int $applicationId, string $reason, int $userId, Request $request): array
    {
        $current = $this->get($applicationId);
        $this->assertTransition((string) $current['status'], 'rejected');
        $this->applications->updateStatus($applicationId, 'rejected', $reason, null, $userId);
        $updated = $this->get($applicationId);
        $this->audit->record($userId, 'update', 'adoptions', 'adoption_applications', $applicationId, $current, $updated, $request);

        return $updated;
    }

    public function scheduleInterview(int $applicationId, array $data, int $userId, Request $request): array
    {
        $current = $this->get($applicationId);
        if (!in_array((string) $current['status'], ['pending_review', 'interview_scheduled'], true)) {
            throw new RuntimeException('This application cannot be scheduled for interview at its current stage.');
        }

        $scheduledDate = $this->normalizeDateTime((string) $data['scheduled_date']);
        if (strtotime($scheduledDate) <= time()) {
            throw new RuntimeException('Interview schedule must be in the future.');
        }

        $payload = [
            'application_id' => $applicationId,
            'scheduled_date' => $scheduledDate,
            'interview_type' => (string) $data['interview_type'],
            'video_call_link' => $this->nullIfBlank($data['video_call_link'] ?? null),
            'location' => $this->nullIfBlank($data['location'] ?? null),
            'status' => 'scheduled',
            'screening_checklist' => null,
            'home_assessment_notes' => null,
            'pet_care_knowledge_score' => null,
            'overall_recommendation' => null,
            'interviewer_notes' => null,
            'conducted_by' => ($data['conducted_by'] ?? '') !== '' ? (int) $data['conducted_by'] : null,
            'completed_at' => null,
        ];

        Database::beginTransaction();
        try {
            $interviewId = $this->interviews->create($payload);
            $this->applications->updateStatus($applicationId, 'interview_scheduled', null, null, $userId);
            $this->createNotification(
                (int) $current['adopter_id'],
                'interview_scheduled',
                'Interview scheduled',
                'Your adoption interview for ' . ($current['animal_name'] ?: 'your selected animal') . ' has been scheduled on ' . date('F j, Y g:i A', strtotime($scheduledDate)) . '.',
                '/adopt/apply'
            );
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get($applicationId);
        $this->audit->record($userId, 'create', 'adoptions', 'adoption_interviews', $interviewId, [], $payload, $request);

        return $updated;
    }

    public function updateInterview(int $interviewId, array $data, int $userId, Request $request): array
    {
        $current = $this->interviews->find($interviewId);
        if ($current === false) {
            throw new RuntimeException('Adoption interview not found.');
        }

        $application = $this->get((int) $current['application_id']);
        $payload = [
            'scheduled_date' => $this->normalizeDateTime((string) $data['scheduled_date']),
            'interview_type' => (string) $data['interview_type'],
            'video_call_link' => $this->nullIfBlank($data['video_call_link'] ?? null),
            'location' => $this->nullIfBlank($data['location'] ?? null),
            'status' => (string) $data['status'],
            'screening_checklist' => $this->screeningChecklistJson($data['screening_checklist'] ?? null),
            'home_assessment_notes' => $this->nullIfBlank($data['home_assessment_notes'] ?? null),
            'pet_care_knowledge_score' => ($data['pet_care_knowledge_score'] ?? '') !== '' ? (int) $data['pet_care_knowledge_score'] : null,
            'overall_recommendation' => $this->nullIfBlank($data['overall_recommendation'] ?? null),
            'interviewer_notes' => $this->nullIfBlank($data['interviewer_notes'] ?? null),
            'conducted_by' => ($data['conducted_by'] ?? '') !== '' ? (int) $data['conducted_by'] : null,
            'completed_at' => (string) $data['status'] === 'completed' ? date('Y-m-d H:i:s') : null,
        ];

        Database::beginTransaction();
        try {
            $this->interviews->update($interviewId, $payload);

            if ((string) $data['status'] === 'completed') {
                $this->setApplicationStatusFromSystem((int) $current['application_id'], 'interview_completed', $userId);
            } elseif ((string) $application['status'] === 'pending_review') {
                $this->setApplicationStatusFromSystem((int) $current['application_id'], 'interview_scheduled', $userId);
            }

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get((int) $current['application_id']);
        $this->audit->record($userId, 'update', 'adoptions', 'adoption_interviews', $interviewId, $current, $payload, $request);

        return $updated;
    }

    public function createSeminar(array $data, int $userId, Request $request): array
    {
        $scheduledDate = $this->normalizeDateTime((string) $data['scheduled_date']);
        $endTime = ($data['end_time'] ?? '') !== '' ? $this->normalizeDateTime((string) $data['end_time']) : null;

        if (strtotime($scheduledDate) <= time()) {
            throw new RuntimeException('Seminar schedule must be in the future.');
        }

        if ($endTime !== null && strtotime($endTime) <= strtotime($scheduledDate)) {
            throw new RuntimeException('Seminar end time must be after the start time.');
        }

        $payload = [
            'title' => trim((string) $data['title']),
            'scheduled_date' => $scheduledDate,
            'end_time' => $endTime,
            'location' => trim((string) $data['location']),
            'capacity' => max(1, (int) $data['capacity']),
            'facilitator_id' => ($data['facilitator_id'] ?? '') !== '' ? (int) $data['facilitator_id'] : null,
            'description' => $this->nullIfBlank($data['description'] ?? null),
            'status' => (string) ($data['status'] ?? 'scheduled'),
            'created_by' => $userId,
        ];

        $seminarId = $this->seminars->create($payload);
        $seminar = $this->seminars->find($seminarId);
        $this->audit->record($userId, 'create', 'adoptions', 'adoption_seminars', $seminarId, [], $seminar ?: $payload, $request);

        return $this->seminarsList();
    }

    public function registerAttendee(int $seminarId, int $applicationId, int $userId, Request $request): array
    {
        $seminar = $this->seminars->find($seminarId);
        if ($seminar === false) {
            throw new RuntimeException('Adoption seminar not found.');
        }

        $application = $this->get($applicationId);
        if (in_array((string) $application['status'], ['completed', 'rejected', 'withdrawn'], true)) {
            throw new RuntimeException('This application is no longer eligible for seminar registration.');
        }

        if ($this->seminars->attendee($seminarId, $applicationId) !== false) {
            throw new RuntimeException('The application is already registered for this seminar.');
        }

        if ((int) $seminar['attendee_count'] >= (int) $seminar['capacity']) {
            throw new RuntimeException('This seminar is already at full capacity.');
        }

        Database::beginTransaction();
        try {
            $attendeeId = $this->seminars->addAttendee($seminarId, $applicationId);
            $this->setApplicationStatusFromSystem($applicationId, 'seminar_scheduled', $userId);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get($applicationId);
        $this->audit->record($userId, 'create', 'adoptions', 'seminar_attendees', $attendeeId, [], [
            'seminar_id' => $seminarId,
            'application_id' => $applicationId,
        ], $request);

        return $updated;
    }

    public function updateAttendance(int $seminarId, int $applicationId, string $attendanceStatus, int $userId, Request $request): array
    {
        $current = $this->seminars->attendee($seminarId, $applicationId);
        if ($current === false) {
            throw new RuntimeException('Seminar attendee registration not found.');
        }

        Database::beginTransaction();
        try {
            $this->seminars->updateAttendance($seminarId, $applicationId, $attendanceStatus, $userId);

            if ($attendanceStatus === 'attended') {
                $targetStatus = $this->billingSummary($applicationId)['payment_state'] === 'pending'
                    ? 'pending_payment'
                    : 'seminar_completed';
                $this->setApplicationStatusFromSystem($applicationId, $targetStatus, $userId);
            }

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get($applicationId);
        $this->audit->record($userId, 'update', 'adoptions', 'seminar_attendees', (int) $current['id'], $current, [
            'attendance_status' => $attendanceStatus,
        ], $request);

        return $updated;
    }

    public function complete(int $applicationId, array $data, int $userId, Request $request): array
    {
        $application = $this->get($applicationId);
        if ($application['completion'] !== null) {
            throw new RuntimeException('This adoption application has already been completed.');
        }

        if (($application['animal_id'] ?? null) === null) {
            throw new RuntimeException('An animal must be assigned before the adoption can be completed.');
        }

        if (!in_array((string) $application['status'], ['seminar_completed', 'pending_payment'], true)) {
            throw new RuntimeException('This application is not yet ready for completion.');
        }

        $billing = $this->billingSummaryFromInvoices($application['invoices']);
        $paymentConfirmed = $this->toBool($data['payment_confirmed'] ?? false);
        if ($billing['payment_state'] === 'pending' && !$paymentConfirmed) {
            throw new RuntimeException('Outstanding adoption billing must be settled or manually confirmed before completion.');
        }

        $completionPayload = [
            'application_id' => $applicationId,
            'animal_id' => (int) $application['animal_id'],
            'adopter_id' => (int) $application['adopter_id'],
            'completion_date' => $this->normalizeDateTime((string) ($data['completion_date'] ?? date('Y-m-d H:i:s'))),
            'payment_confirmed' => $paymentConfirmed ? 1 : 0,
            'contract_signed' => $this->toBool($data['contract_signed'] ?? false) ? 1 : 0,
            'contract_signature_path' => null,
            'medical_records_provided' => $this->toBool($data['medical_records_provided'] ?? false) ? 1 : 0,
            'spay_neuter_agreement' => $this->toBool($data['spay_neuter_agreement'] ?? false) ? 1 : 0,
            'certificate_path' => null,
            'notes' => $this->nullIfBlank($data['notes'] ?? null),
            'processed_by' => $userId,
        ];

        Database::beginTransaction();
        try {
            $completionId = $this->completions->create($completionPayload);
            $completion = $this->completions->findByApplication($applicationId);
            if ($completion === false) {
                throw new RuntimeException('Adoption completion record was not created.');
            }

            $certificatePath = $this->pdfs->adoptionCertificate($application, $completion);
            $this->completions->updateCertificatePath($completionId, $certificatePath);

            $this->applications->updateStatus($applicationId, 'completed', null, null, $userId);
            $this->animals->updateStatus(
                (int) $application['animal_id'],
                'Adopted',
                'Adoption application completed.',
                $completionPayload['completion_date'],
                $userId
            );
            $this->animals->releaseKennelOccupancy((int) $application['animal_id'], $userId);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->get($applicationId);
        $this->audit->record($userId, 'create', 'adoptions', 'adoption_completions', $completionId, [], $updated['completion'] ?? [], $request);

        return $updated;
    }

    public function certificate(int $applicationId): array
    {
        $application = $this->get($applicationId);
        $completion = $application['completion'];

        if (!is_array($completion) || ($completion['certificate_path'] ?? null) === null) {
            throw new RuntimeException('Adoption certificate not found.');
        }

        return $completion;
    }

    private function linkedInvoices(int $applicationId): array
    {
        return Database::fetchAll(
            'SELECT i.*
             FROM invoices i
             WHERE i.application_id = :application_id
               AND i.is_deleted = 0
             ORDER BY i.created_at DESC, i.id DESC',
            ['application_id' => $applicationId]
        );
    }

    private function billingSummary(int $applicationId): array
    {
        return $this->billingSummaryFromInvoices($this->linkedInvoices($applicationId));
    }

    private function billingSummaryFromInvoices(array $invoices): array
    {
        $summary = [
            'invoice_count' => count($invoices),
            'total_amount' => 0.0,
            'amount_paid' => 0.0,
            'balance_due' => 0.0,
            'payment_state' => 'none',
        ];

        if ($invoices === []) {
            return $summary;
        }

        $hasPending = false;
        $allPaid = true;

        foreach ($invoices as $invoice) {
            $summary['total_amount'] += (float) ($invoice['total_amount'] ?? 0);
            $summary['amount_paid'] += (float) ($invoice['amount_paid'] ?? 0);
            $summary['balance_due'] += (float) ($invoice['balance_due'] ?? 0);

            if (($invoice['payment_status'] ?? 'unpaid') !== 'paid') {
                $allPaid = false;
            }

            if (in_array((string) ($invoice['payment_status'] ?? ''), ['unpaid', 'partial'], true)) {
                $hasPending = true;
            }
        }

        $summary['payment_state'] = $allPaid ? 'paid' : ($hasPending ? 'pending' : 'mixed');

        return $summary;
    }

    private function setApplicationStatusFromSystem(int $applicationId, string $targetStatus, int $userId): void
    {
        $current = $this->applications->find($applicationId);
        if ($current === false || (string) $current['status'] === $targetStatus) {
            return;
        }

        if (!in_array($targetStatus, self::STATUS_FLOW[(string) $current['status']] ?? [], true)) {
            return;
        }

        $this->applications->updateStatus($applicationId, $targetStatus, null, null, $userId);
    }

    private function assertTransition(string $currentStatus, string $targetStatus): void
    {
        if (!isset(self::STATUSES[$targetStatus])) {
            throw new RuntimeException('Unknown adoption status.');
        }

        if ($currentStatus === $targetStatus) {
            return;
        }

        if (!in_array($targetStatus, self::STATUS_FLOW[$currentStatus] ?? [], true)) {
            throw new RuntimeException('The requested status transition is not allowed.');
        }
    }

    private function screeningChecklistJson(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Screening checklist must be valid JSON.');
        }

        return $value;
    }

    private function normalizeDateTime(string $value): string
    {
        $value = str_replace('T', ' ', trim($value));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value . ' 00:00:00';
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        return $value;
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function storePortalDocument(array $file, string $prefix): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('A valid ID document is required.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $directory = dirname(__DIR__, 2) . '/public/uploads/adoptions/documents';

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

    private function toBool(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    private function daysInStage(string $updatedAt): int
    {
        $updatedTimestamp = strtotime($updatedAt);
        if ($updatedTimestamp === false) {
            return 0;
        }

        return max(0, (int) floor((time() - $updatedTimestamp) / 86400));
    }

    private function createNotification(int $userId, string $type, string $title, string $message, ?string $link): void
    {
        Database::execute(
            'INSERT INTO notifications (user_id, type, title, message, link)
             VALUES (:user_id, :type, :title, :message, :link)',
            [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'message' => $message,
                'link' => $link,
            ]
        );
    }
}
