<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class AdoptionApplication
{
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = Database::fetchAll(
            "SELECT aa.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS adopter_name,
                    u.email AS adopter_email,
                    u.phone AS adopter_phone,
                    a.animal_id AS animal_code,
                    a.name AS animal_name,
                    a.species AS animal_species
             FROM adoption_applications aa
             INNER JOIN users u ON u.id = aa.adopter_id
             LEFT JOIN animals a ON a.id = aa.animal_id
             {$whereSql}
             ORDER BY aa.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $count = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM adoption_applications aa
             INNER JOIN users u ON u.id = aa.adopter_id
             LEFT JOIN animals a ON a.id = aa.animal_id
             {$whereSql}",
            $bindings
        );

        return [
            'items' => $rows,
            'total' => (int) ($count['aggregate'] ?? 0),
        ];
    }

    public function find(int $id): array|false
    {
        return Database::fetch(
            "SELECT aa.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS adopter_name,
                    u.email AS adopter_email,
                    u.phone AS adopter_phone,
                    u.address_line1,
                    u.address_line2,
                    u.city,
                    u.province,
                    u.zip_code,
                    a.animal_id AS animal_code,
                    a.name AS animal_name,
                    a.species AS animal_species,
                    a.status AS animal_status
             FROM adoption_applications aa
             INNER JOIN users u ON u.id = aa.adopter_id
             LEFT JOIN animals a ON a.id = aa.animal_id
             WHERE aa.id = :id
               AND aa.is_deleted = 0
             LIMIT 1",
            ['id' => $id]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO adoption_applications (
                application_number, adopter_id, animal_id, status, preferred_species, preferred_breed,
                preferred_age_min, preferred_age_max, preferred_size, preferred_gender, housing_type,
                housing_ownership, has_yard, yard_size, num_adults, num_children, children_ages,
                existing_pets_description, previous_pet_experience, vet_reference_name, vet_reference_clinic,
                vet_reference_contact, valid_id_path, digital_signature_path, agrees_to_policies,
                agrees_to_home_visit, agrees_to_return_policy, created_by, updated_by
             ) VALUES (
                :application_number, :adopter_id, :animal_id, :status, :preferred_species, :preferred_breed,
                :preferred_age_min, :preferred_age_max, :preferred_size, :preferred_gender, :housing_type,
                :housing_ownership, :has_yard, :yard_size, :num_adults, :num_children, :children_ages,
                :existing_pets_description, :previous_pet_experience, :vet_reference_name, :vet_reference_clinic,
                :vet_reference_contact, :valid_id_path, :digital_signature_path, :agrees_to_policies,
                :agrees_to_home_visit, :agrees_to_return_policy, :created_by, :updated_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $rejectionReason, ?string $withdrawnReason, ?int $updatedBy): void
    {
        Database::execute(
            'UPDATE adoption_applications
             SET status = :status,
                 rejection_reason = :rejection_reason,
                 withdrawn_reason = :withdrawn_reason,
                 updated_by = :updated_by
             WHERE id = :id',
            [
                'id' => $id,
                'status' => $status,
                'rejection_reason' => $rejectionReason,
                'withdrawn_reason' => $withdrawnReason,
                'updated_by' => $updatedBy,
            ]
        );
    }

    public function buildPipelineStats(): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) AS aggregate
             FROM adoption_applications
             WHERE is_deleted = 0
             GROUP BY status"
        );

        $stats = [];
        foreach ($rows as $row) {
            $stats[(string) $row['status']] = (int) $row['aggregate'];
        }

        return $stats;
    }

    private function buildFilters(array $filters): array
    {
        $clauses = ['aa.is_deleted = 0'];
        $bindings = [];

        if (($filters['search'] ?? '') !== '') {
            $clauses[] = "(aa.application_number LIKE :search
                OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search
                OR u.email LIKE :search
                OR a.animal_id LIKE :search
                OR a.name LIKE :search)";
            $bindings['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (($filters['status'] ?? '') !== '') {
            $clauses[] = 'aa.status = :status';
            $bindings['status'] = $filters['status'];
        }

        if (($filters['animal_id'] ?? '') !== '') {
            $clauses[] = 'aa.animal_id = :animal_id';
            $bindings['animal_id'] = (int) $filters['animal_id'];
        }

        if (($filters['adopter_id'] ?? '') !== '') {
            $clauses[] = 'aa.adopter_id = :adopter_id';
            $bindings['adopter_id'] = (int) $filters['adopter_id'];
        }

        return ['WHERE ' . implode(' AND ', $clauses), $bindings];
    }
}
