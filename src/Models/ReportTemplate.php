<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class ReportTemplate
{
    public function listForUser(int $userId): array
    {
        $rows = Database::fetchAll(
            'SELECT rt.*, CONCAT(u.first_name, " ", u.last_name) AS created_by_name
             FROM report_templates rt
             LEFT JOIN users u ON u.id = rt.created_by
             WHERE rt.is_system = 1
                OR rt.created_by = :user_id
             ORDER BY rt.is_system DESC, rt.name ASC',
            ['user_id' => $userId]
        );

        foreach ($rows as &$row) {
            $row['configuration'] = json_decode((string) $row['configuration'], true) ?: [];
            $row['is_system'] = (int) $row['is_system'] === 1;
        }

        return $rows;
    }

    public function findAccessible(int $id, int $userId): array|false
    {
        $row = Database::fetch(
            'SELECT *
             FROM report_templates
             WHERE id = :id
               AND (is_system = 1 OR created_by = :user_id)
             LIMIT 1',
            ['id' => $id, 'user_id' => $userId]
        );

        if ($row !== false) {
            $row['configuration'] = json_decode((string) $row['configuration'], true) ?: [];
            $row['is_system'] = (int) $row['is_system'] === 1;
        }

        return $row;
    }

    public function create(string $name, string $reportType, array $configuration, int $createdBy): int
    {
        Database::execute(
            'INSERT INTO report_templates (name, report_type, configuration, is_system, created_by)
             VALUES (:name, :report_type, :configuration, 0, :created_by)',
            [
                'name' => $name,
                'report_type' => $reportType,
                'configuration' => json_encode($configuration, JSON_UNESCAPED_SLASHES),
                'created_by' => $createdBy,
            ]
        );

        return (int) Database::lastInsertId();
    }
}
