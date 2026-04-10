<?php

declare(strict_types=1);

namespace App\Models;

class ReportTemplate extends BaseModel
{
    protected static string $table = 'report_templates';
    protected static bool $useSoftDeletes = false; // System templates don't typically use soft deletes here

    public function listForUser(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT rt.*, CONCAT(u.first_name, " ", u.last_name) AS created_by_name
             FROM report_templates rt
             LEFT JOIN users u ON u.id = rt.created_by
             WHERE rt.is_system = 1
                OR rt.created_by = :user_id
             ORDER BY rt.is_system DESC, rt.name ASC',
            ['user_id' => $userId]
        );

        foreach ($rows as &$row) {
            $row['configuration'] = json_decode((string) ($row['configuration'] ?? '{}'), true) ?: [];
            $row['is_system'] = (int) ($row['is_system'] ?? 0) === 1;
        }

        return $rows;
    }

    public function findAccessible(int $id, int $userId): array|false
    {
        $row = $this->db->fetch(
            'SELECT *
             FROM report_templates
             WHERE id = :id
               AND (is_system = 1 OR created_by = :user_id)
             LIMIT 1',
            ['id' => $id, 'user_id' => $userId]
        );

        if ($row !== false) {
            $row['configuration'] = json_decode((string) ($row['configuration'] ?? '{}'), true) ?: [];
            $row['is_system'] = (int) ($row['is_system'] ?? 0) === 1;
        }

        return $row;
    }

    public function createTemplate(string $name, string $reportType, array $configuration, int $createdBy): int
    {
        return $this->create([
            'name' => $name,
            'report_type' => $reportType,
            'configuration' => json_encode($configuration, JSON_UNESCAPED_SLASHES),
            'is_system' => 0,
            'created_by' => $createdBy,
        ]);
    }
}
