<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class KennelMaintenanceLog
{
    public function listByKennel(int $kennelId): array
    {
        return Database::fetchAll(
            'SELECT *
             FROM kennel_maintenance_logs
             WHERE kennel_id = :kennel_id
             ORDER BY COALESCE(completed_at, CONCAT(scheduled_date, " 00:00:00"), created_at) DESC, id DESC',
            ['kennel_id' => $kennelId]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO kennel_maintenance_logs (
                kennel_id, maintenance_type, description, scheduled_date, completed_at, performed_by
             ) VALUES (
                :kennel_id, :maintenance_type, :description, :scheduled_date, :completed_at, :performed_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }
}
