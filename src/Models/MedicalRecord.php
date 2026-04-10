<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Pagination\PaginatedWindow;

class MedicalRecord extends BaseModel
{
    protected static string $table = 'medical_records';

    public function paginate(array $filters, int $page, int $perPage): array
    {
        [$whereSql, $bindings] = $this->buildFilters($filters);
        $offset = ($page - 1) * $perPage;

        $rows = $this->db->fetchAll(
            "SELECT mr.*, a.animal_id AS animal_code, a.name AS animal_name,
                    CONCAT_WS(' ', v.first_name, v.last_name) AS veterinarian_name
             FROM medical_records mr
             INNER JOIN animals a ON a.id = mr.animal_id
             INNER JOIN users v ON v.id = mr.veterinarian_id
             {$whereSql}
             ORDER BY mr.record_date DESC, mr.id DESC
             LIMIT " . ($perPage + 1) . " OFFSET {$offset}",
            $bindings
        );

        return PaginatedWindow::resolve(
            $rows,
            $page,
            $perPage,
            fn (): int => (int) (($this->db->fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM medical_records mr
                 INNER JOIN animals a ON a.id = mr.animal_id
                 INNER JOIN users v ON v.id = mr.veterinarian_id
                 {$whereSql}",
                $bindings
            )['aggregate'] ?? 0))
        );
    }

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->db->fetch(
            'SELECT mr.*, a.animal_id AS animal_code, a.name AS animal_name, a.status AS animal_status,
                    CONCAT_WS(" ", v.first_name, v.last_name) AS veterinarian_name,
                    CONCAT_WS(" ", c.first_name, c.last_name) AS created_by_name,
                    CONCAT_WS(" ", u.first_name, u.last_name) AS updated_by_name
             FROM medical_records mr
             INNER JOIN animals a ON a.id = mr.animal_id
             INNER JOIN users v ON v.id = mr.veterinarian_id
             LEFT JOIN users c ON c.id = mr.created_by
             LEFT JOIN users u ON u.id = mr.updated_by
             WHERE mr.id = :id
               AND (mr.is_deleted = 0 OR :include_deleted = 1)
             LIMIT 1',
            ['id' => $id, 'include_deleted' => $includeDeleted ? 1 : 0]
        );
    }

    public function listByAnimal(int $animalId): array
    {
        return $this->db->fetchAll(
            'SELECT mr.*, a.animal_id AS animal_code, a.name AS animal_name,
                    CONCAT_WS(" ", v.first_name, v.last_name) AS veterinarian_name
             FROM medical_records mr
             INNER JOIN animals a ON a.id = mr.animal_id
             INNER JOIN users v ON v.id = mr.veterinarian_id
             WHERE mr.animal_id = :animal_id
               AND mr.is_deleted = 0
             ORDER BY mr.record_date DESC, mr.id DESC',
            ['animal_id' => $animalId]
        );
    }

    public function dueVaccinations(): array
    {
        return $this->db->fetchAll(
            'SELECT mr.id, mr.record_date, vr.next_due_date, vr.vaccine_name, vr.dose_number,
                    a.id AS animal_id, a.animal_id AS animal_code, a.name AS animal_name,
                    DATEDIFF(vr.next_due_date, CURDATE()) AS days_until_due
             FROM vaccination_records vr
             INNER JOIN medical_records mr ON mr.id = vr.medical_record_id
             INNER JOIN animals a ON a.id = mr.animal_id
             WHERE mr.is_deleted = 0
               AND vr.next_due_date IS NOT NULL
               AND vr.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY vr.next_due_date ASC, a.name ASC'
        );
    }

    public function dueDewormings(): array
    {
        return $this->db->fetchAll(
            'SELECT mr.id, mr.record_date, dr.next_due_date, dr.dewormer_name,
                    a.id AS animal_id, a.animal_id AS animal_code, a.name AS animal_name,
                    DATEDIFF(dr.next_due_date, CURDATE()) AS days_until_due
             FROM deworming_records dr
             INNER JOIN medical_records mr ON mr.id = dr.medical_record_id
             INNER JOIN animals a ON a.id = mr.animal_id
             WHERE mr.is_deleted = 0
               AND dr.next_due_date IS NOT NULL
               AND dr.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
             ORDER BY dr.next_due_date ASC, a.name ASC'
        );
    }

    public function dueSummary(): array
    {
        $row = $this->db->fetch(
            "SELECT
                (
                    SELECT COUNT(*)
                    FROM vaccination_records vr
                    INNER JOIN medical_records mr ON mr.id = vr.medical_record_id
                    WHERE mr.is_deleted = 0
                      AND vr.next_due_date IS NOT NULL
                      AND vr.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ) AS due_vaccinations,
                (
                    SELECT COUNT(*)
                    FROM deworming_records dr
                    INNER JOIN medical_records mr ON mr.id = dr.medical_record_id
                    WHERE mr.is_deleted = 0
                      AND dr.next_due_date IS NOT NULL
                      AND dr.next_due_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                ) AS due_dewormings"
        );

        return [
            'due_vaccinations' => (int) ($row['due_vaccinations'] ?? 0),
            'due_dewormings' => (int) ($row['due_dewormings'] ?? 0),
        ];
    }

    private function buildFilters(array $filters): array
    {
        $clauses = ['mr.is_deleted = 0'];
        $bindings = [];

        if (($filters['search'] ?? '') !== '') {
            $clauses[] = '(a.animal_id LIKE :search OR a.name LIKE :search OR mr.general_notes LIKE :search)';
            $bindings['search'] = '%' . $filters['search'] . '%';
        }

        if (($filters['procedure_type'] ?? '') !== '') {
            $clauses[] = 'mr.procedure_type = :procedure_type';
            $bindings['procedure_type'] = $filters['procedure_type'];
        }

        if (($filters['animal_id'] ?? '') !== '') {
            $clauses[] = 'mr.animal_id = :animal_id';
            $bindings['animal_id'] = (int) $filters['animal_id'];
        }

        if (($filters['veterinarian_id'] ?? '') !== '') {
            $clauses[] = 'mr.veterinarian_id = :veterinarian_id';
            $bindings['veterinarian_id'] = (int) $filters['veterinarian_id'];
        }

        if (($filters['date_from'] ?? '') !== '') {
            $clauses[] = 'DATE(mr.record_date) >= :date_from';
            $bindings['date_from'] = $filters['date_from'];
        }

        if (($filters['date_to'] ?? '') !== '') {
            $clauses[] = 'DATE(mr.record_date) <= :date_to';
            $bindings['date_to'] = $filters['date_to'];
        }

        return ['WHERE ' . implode(' AND ', $clauses), $bindings];
    }
}
