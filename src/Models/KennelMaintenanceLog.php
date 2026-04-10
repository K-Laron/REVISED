<?php

declare(strict_types=1);

namespace App\Models;

class KennelMaintenanceLog extends BaseModel
{
    protected static string $table = 'kennel_maintenance_logs';
    protected static bool $useSoftDeletes = false; // Maintenance logs don't seem to have is_deleted in the current schema

    public function listByKennel(int $kennelId): array
    {
        return $this->db->fetchAll(
            'SELECT *
             FROM kennel_maintenance_logs
             WHERE kennel_id = :kennel_id
             ORDER BY COALESCE(completed_at, CONCAT(scheduled_date, " 00:00:00"), created_at) DESC, id DESC',
            ['kennel_id' => $kennelId]
        );
    }
}
