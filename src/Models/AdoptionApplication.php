<?php

declare(strict_types=1);

namespace App\Models;

class AdoptionApplication extends BaseModel
{
    protected static string $table = 'adoption_applications';

    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->fetchAll(
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

        $count = $this->db->fetch(
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

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->db->fetch(
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
               AND (aa.is_deleted = 0 OR :include_deleted = 1)
             LIMIT 1",
            ['id' => $id, 'include_deleted' => $includeDeleted ? 1 : 0]
        );
    }

    public function updateStatus(int $id, string $status, ?string $rejectionReason, ?string $withdrawnReason, ?int $updatedBy): void
    {
        $this->update($id, [
            'status' => $status,
            'rejection_reason' => $rejectionReason,
            'withdrawn_reason' => $withdrawnReason,
            'updated_by' => $updatedBy,
        ]);
    }

    public function pipelineMetrics(): array
    {
        return $this->db->fetchAll(
            "SELECT metric_group, metric_key, metric_value
             FROM (
                 SELECT 'status' AS metric_group, status AS metric_key, COUNT(*) AS metric_value
                 FROM adoption_applications
                 WHERE is_deleted = 0
                 GROUP BY status

                 UNION ALL

                 SELECT 'summary' AS metric_group, 'upcoming_interviews' AS metric_key, COUNT(*) AS metric_value
                 FROM adoption_interviews
                 WHERE status = 'scheduled'
                   AND scheduled_date >= NOW()

                 UNION ALL

                 SELECT 'summary' AS metric_group, 'upcoming_seminars' AS metric_key, COUNT(*) AS metric_value
                 FROM adoption_seminars
                 WHERE status IN ('scheduled', 'in_progress')
                   AND scheduled_date >= NOW()
             ) AS pipeline_metrics"
        );
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

    public function findLatestByAnimal(int $animalId): array|false
    {
        return $this->db->fetch(
            "SELECT aa.*,
                    CONCAT(u.first_name, ' ', u.last_name) AS adopter_name,
                    u.email AS adopter_email
             FROM adoption_applications aa
             INNER JOIN users u ON u.id = aa.adopter_id
             WHERE aa.animal_id = :animal_id
               AND aa.is_deleted = 0
             ORDER BY aa.created_at DESC
             LIMIT 1",
            ['animal_id' => $animalId]
        );
    }

    public function listForAdopter(int $userId): array
    {
        return $this->db->fetchAll(
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
}
