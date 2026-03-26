<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Animal
{
    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = Database::fetchAll(
            "SELECT a.*, b.name AS breed_name, p.file_path AS primary_photo_path
             FROM animals a
             LEFT JOIN breeds b ON b.id = a.breed_id
             LEFT JOIN animal_photos p ON p.animal_id = a.id AND p.is_primary = 1
             {$whereSql}
             ORDER BY a.created_at DESC
             LIMIT {$perPage} OFFSET {$offset}",
            $bindings
        );

        $countRow = Database::fetch(
            "SELECT COUNT(*) AS aggregate
             FROM animals a
             {$whereSql}",
            $bindings
        );

        return [
            'items' => $rows,
            'total' => (int) ($countRow['aggregate'] ?? 0),
        ];
    }

    public function create(array $data): int
    {
        $bindings = $data;
        unset($bindings['kennel_id']);

        Database::execute(
            'INSERT INTO animals (
                animal_id, name, species, breed_id, breed_other, gender, age_years, age_months, color_markings, size,
                weight_kg, distinguishing_features, special_needs_notes, microchip_number, spay_neuter_status,
                intake_type, intake_date, location_found, barangay_of_origin, impoundment_order_number,
                authority_name, authority_position, authority_contact,
                brought_by_name, brought_by_contact, brought_by_address, impounding_officer_name,
                surrender_reason, condition_at_intake, vaccination_status_at_intake, temperament,
                status, status_reason, status_changed_at, created_by, updated_by
             ) VALUES (
                :animal_id, :name, :species, :breed_id, :breed_other, :gender, :age_years, :age_months, :color_markings, :size,
                :weight_kg, :distinguishing_features, :special_needs_notes, :microchip_number, :spay_neuter_status,
                :intake_type, :intake_date, :location_found, :barangay_of_origin, :impoundment_order_number,
                :authority_name, :authority_position, :authority_contact,
                :brought_by_name, :brought_by_contact, :brought_by_address, :impounding_officer_name,
                :surrender_reason, :condition_at_intake, :vaccination_status_at_intake, :temperament,
                :status, :status_reason, :status_changed_at, :created_by, :updated_by
             )',
            $bindings
        );

        return (int) Database::lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $bindings = $data;
        unset($bindings['kennel_id'], $bindings['animal_id'], $bindings['status'], $bindings['status_reason'], $bindings['status_changed_at'], $bindings['created_by']);
        $bindings['id'] = $id;

        Database::execute(
            'UPDATE animals SET
                name = :name,
                species = :species,
                breed_id = :breed_id,
                breed_other = :breed_other,
                gender = :gender,
                age_years = :age_years,
                age_months = :age_months,
                color_markings = :color_markings,
                size = :size,
                weight_kg = :weight_kg,
                distinguishing_features = :distinguishing_features,
                special_needs_notes = :special_needs_notes,
                microchip_number = :microchip_number,
                spay_neuter_status = :spay_neuter_status,
                intake_type = :intake_type,
                intake_date = :intake_date,
                location_found = :location_found,
                barangay_of_origin = :barangay_of_origin,
                impoundment_order_number = :impoundment_order_number,
                authority_name = :authority_name,
                authority_position = :authority_position,
                authority_contact = :authority_contact,
                brought_by_name = :brought_by_name,
                brought_by_contact = :brought_by_contact,
                brought_by_address = :brought_by_address,
                impounding_officer_name = :impounding_officer_name,
                surrender_reason = :surrender_reason,
                condition_at_intake = :condition_at_intake,
                vaccination_status_at_intake = :vaccination_status_at_intake,
                temperament = :temperament,
                updated_by = :updated_by
             WHERE id = :id',
            $bindings
        );
    }

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        $column = is_numeric($id) ? 'a.id' : 'a.animal_id';
        $bindings = ['value' => $id];

        $sql = "SELECT a.*, b.name AS breed_name, q.qr_data, q.file_path AS qr_file_path
                FROM animals a
                LEFT JOIN breeds b ON b.id = a.breed_id
                LEFT JOIN animal_qr_codes q ON q.animal_id = a.id
                WHERE {$column} = :value";

        if (!$includeDeleted) {
            $sql .= ' AND a.is_deleted = 0';
        }

        $sql .= ' ORDER BY q.generated_at DESC LIMIT 1';

        return Database::fetch($sql, $bindings);
    }

    public function setDeleted(int $id, bool $deleted, ?int $userId): void
    {
        Database::execute(
            'UPDATE animals
             SET is_deleted = :is_deleted,
                 deleted_at = :deleted_at,
                 deleted_by = :deleted_by
             WHERE id = :id',
            [
                'id' => $id,
                'is_deleted' => $deleted ? 1 : 0,
                'deleted_at' => $deleted ? date('Y-m-d H:i:s') : null,
                'deleted_by' => $deleted ? $userId : null,
            ]
        );
    }

    public function updateStatus(int $id, string $status, ?string $reason, ?string $outcomeDate, int $userId): void
    {
        Database::execute(
            'UPDATE animals
             SET status = :status,
                 status_reason = :status_reason,
                 status_changed_at = :status_changed_at,
                 outcome_date = :outcome_date,
                 updated_by = :updated_by
             WHERE id = :id',
            [
                'id' => $id,
                'status' => $status,
                'status_reason' => $reason,
                'status_changed_at' => date('Y-m-d H:i:s'),
                'outcome_date' => $outcomeDate,
                'updated_by' => $userId,
            ]
        );
    }

    public function currentKennel(int $animalId): array|false
    {
        return Database::fetch(
            'SELECT k.*
             FROM kennel_assignments ka
             INNER JOIN kennels k ON k.id = ka.kennel_id
             WHERE ka.animal_id = :animal_id AND ka.released_at IS NULL
             ORDER BY ka.assigned_at DESC
             LIMIT 1',
            ['animal_id' => $animalId]
        );
    }

    public function assignKennel(int $animalId, ?int $kennelId, ?int $userId): void
    {
        Database::execute(
            'UPDATE kennel_assignments
             SET released_at = NOW(), released_by = :released_by, transfer_reason = :transfer_reason
             WHERE animal_id = :animal_id AND released_at IS NULL',
            [
                'animal_id' => $animalId,
                'released_by' => $userId,
                'transfer_reason' => 'Reassigned from animal intake/update',
            ]
        );

        if ($kennelId === null) {
            return;
        }

        Database::execute(
            'INSERT INTO kennel_assignments (kennel_id, animal_id, assigned_by)
             VALUES (:kennel_id, :animal_id, :assigned_by)',
            [
                'kennel_id' => $kennelId,
                'animal_id' => $animalId,
                'assigned_by' => $userId,
            ]
        );

        Database::execute("UPDATE kennels SET status = 'Occupied', updated_by = :updated_by WHERE id = :id", [
            'id' => $kennelId,
            'updated_by' => $userId,
        ]);
    }

    public function releaseKennelOccupancy(int $animalId, ?int $userId): void
    {
        $current = $this->currentKennel($animalId);
        if ($current === false) {
            return;
        }

        Database::execute(
            'UPDATE kennel_assignments SET released_at = NOW(), released_by = :released_by WHERE animal_id = :animal_id AND released_at IS NULL',
            ['animal_id' => $animalId, 'released_by' => $userId]
        );

        Database::execute("UPDATE kennels SET status = 'Available', updated_by = :updated_by WHERE id = :id", [
            'id' => $current['id'],
            'updated_by' => $userId,
        ]);
    }

    public function kennelHistory(int $animalId): array
    {
        return Database::fetchAll(
            'SELECT ka.*, k.kennel_code, k.zone, k.size_category
             FROM kennel_assignments ka
             INNER JOIN kennels k ON k.id = ka.kennel_id
             WHERE ka.animal_id = :animal_id
             ORDER BY ka.assigned_at DESC',
            ['animal_id' => $animalId]
        );
    }

    public function medicalRecords(int $animalId): array
    {
        return Database::fetchAll(
            'SELECT id, procedure_type, record_date, general_notes
             FROM medical_records
             WHERE animal_id = :animal_id AND is_deleted = 0
             ORDER BY record_date DESC',
            ['animal_id' => $animalId]
        );
    }

    private function buildFilters(array $filters): array
    {
        $clauses = ['a.is_deleted = 0'];
        $bindings = [];

        if (($filters['search'] ?? '') !== '') {
            $clauses[] = '(a.name LIKE :search OR a.animal_id LIKE :search)';
            $bindings['search'] = '%' . $filters['search'] . '%';
        }

        foreach (['species', 'status', 'intake_type', 'gender', 'size'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $clauses[] = "a.{$field} = :{$field}";
                $bindings[$field] = $filters[$field];
            }
        }

        if (($filters['date_from'] ?? '') !== '') {
            $clauses[] = 'DATE(a.intake_date) >= :date_from';
            $bindings['date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? '') !== '') {
            $clauses[] = 'DATE(a.intake_date) <= :date_to';
            $bindings['date_to'] = $filters['date_to'];
        }

        return ['WHERE ' . implode(' AND ', $clauses), $bindings];
    }
}
