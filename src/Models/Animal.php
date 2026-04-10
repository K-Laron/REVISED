<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Pagination\PaginatedWindow;

class Animal extends BaseModel
{
    protected static string $table = 'animals';

    public function reconcileCompletedAdoptions(?int $animalId = null): void
    {
        $sql = "UPDATE animals a
                INNER JOIN adoption_applications aa ON aa.animal_id = a.id
                LEFT JOIN adoption_completions ac ON ac.application_id = aa.id
                SET a.status = 'Adopted',
                    a.status_reason = 'Adoption application completed.',
                    a.status_changed_at = COALESCE(ac.completion_date, aa.updated_at, aa.created_at, a.status_changed_at, NOW()),
                    a.outcome_date = COALESCE(ac.completion_date, aa.updated_at, aa.created_at, a.outcome_date, NOW()),
                    a.updated_by = COALESCE(aa.updated_by, a.updated_by)
                WHERE a.is_deleted = 0
                  AND aa.is_deleted = 0
                  AND aa.status = 'completed'
                  AND a.status <> 'Adopted'";
        $bindings = [];

        if ($animalId !== null) {
            $sql .= ' AND a.id = :animal_id';
            $bindings['animal_id'] = $animalId;
        }

        $this->db->execute($sql, $bindings);
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

        return $this->db->fetch($sql, $bindings);
    }

    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->fetchAll(
            "SELECT a.*, b.name AS breed_name, p.file_path AS primary_photo_path
             FROM animals a
             LEFT JOIN breeds b ON b.id = a.breed_id
             LEFT JOIN animal_photos p ON p.animal_id = a.id AND p.is_primary = 1
             {$whereSql}
             ORDER BY a.created_at DESC
             LIMIT " . ($perPage + 1) . " OFFSET {$offset}",
            $bindings
        );

        return PaginatedWindow::resolve(
            $rows,
            $page,
            $perPage,
            fn (): int => (int) (($this->db->fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM animals a
                 {$whereSql}",
                $bindings
            )['aggregate'] ?? 0))
        );
    }

    public function searchOptions(): array
    {
        return $this->db->fetchAll(
            'SELECT id, animal_id, name, species, status
             FROM animals
             WHERE is_deleted = 0
             ORDER BY created_at DESC, id DESC'
        );
    }

    public function updateStatus(int $id, string $status, ?string $reason, ?string $outcomeDate, int $userId): void
    {
        $this->db->execute(
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
        return $this->db->fetch(
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
        $this->db->execute(
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

        $this->db->execute(
            'INSERT INTO kennel_assignments (kennel_id, animal_id, assigned_by)
             VALUES (:kennel_id, :animal_id, :assigned_by)',
            [
                'kennel_id' => $kennelId,
                'animal_id' => $animalId,
                'assigned_by' => $userId,
            ]
        );

        $this->db->execute("UPDATE kennels SET status = 'Occupied', updated_by = :updated_by WHERE id = :id", [
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

        $this->db->execute(
            'UPDATE kennel_assignments SET released_at = NOW(), released_by = :released_by WHERE animal_id = :animal_id AND released_at IS NULL',
            ['animal_id' => $animalId, 'released_by' => $userId]
        );

        $this->db->execute("UPDATE kennels SET status = 'Available', updated_by = :updated_by WHERE id = :id", [
            'id' => $current['id'],
            'updated_by' => $userId,
        ]);
    }

    public function kennelHistory(int $animalId): array
    {
        return $this->db->fetchAll(
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
        return $this->db->fetchAll(
            'SELECT id, procedure_type, record_date, general_notes
             FROM medical_records
             WHERE animal_id = :animal_id AND is_deleted = 0
             ORDER BY record_date DESC',
            ['animal_id' => $animalId]
        );
    }

    public function getCensusDetails(): array
    {
        return $this->db->fetchAll(
            'SELECT status,
                    species,
                    COUNT(*) AS total_animals
             FROM animals
             WHERE is_deleted = 0
             GROUP BY status, species
             ORDER BY status ASC, species ASC'
        );
    }

    public function getCensusSummary(): array
    {
        return $this->db->fetch(
            'SELECT COUNT(*) AS total_animals,
                    SUM(status = "Available") AS available,
                    SUM(status = "Adopted") AS adopted,
                    SUM(status = "Under Treatment") AS under_treatment,
                    SUM(status = "Quarantine") AS quarantine
             FROM animals
             WHERE is_deleted = 0'
        ) ?: [];
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

    public function listAssignable(): array
    {
        return $this->db->fetchAll(
            "SELECT a.id, a.animal_id, a.name, a.species, a.size, a.status, k.kennel_code AS current_kennel_code
             FROM animals a
             LEFT JOIN kennel_assignments ka ON ka.animal_id = a.id AND ka.released_at IS NULL
             LEFT JOIN kennels k ON k.id = ka.kennel_id
             WHERE a.is_deleted = 0
               AND a.status NOT IN ('Adopted', 'Deceased', 'Transferred')
             ORDER BY a.created_at DESC"
        );
    }

    public function listFeatured(int $limit = 6): array
    {
        return $this->db->fetchAll(
            "SELECT a.id, a.animal_id, a.name, a.species, a.gender, a.size, a.age_years, a.age_months,
                    a.temperament, b.name AS breed_name, p.file_path AS primary_photo_path
             FROM animals a
             LEFT JOIN breeds b ON b.id = a.breed_id
             LEFT JOIN animal_photos p ON p.animal_id = a.id AND p.is_primary = 1
             WHERE a.is_deleted = 0
               AND a.status = 'Available'
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT :limit",
            ['limit' => $limit]
        );
    }

    public function listAvailableForPortal(array $filters, int $page, int $perPage): array
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

        $items = $this->db->fetchAll(
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

        $count = $this->db->fetch(
            "SELECT COUNT(*) AS aggregate
             FROM animals a
             LEFT JOIN breeds b ON b.id = a.breed_id
             {$whereSql}",
            $bindings
        );

        return [
            'items' => $items,
            'total' => (int) ($count['aggregate'] ?? 0),
        ];
    }
}
