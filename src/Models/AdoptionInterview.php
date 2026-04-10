<?php

declare(strict_types=1);

namespace App\Models;

class AdoptionInterview extends BaseModel
{
    protected static string $table = 'adoption_interviews';
    protected static bool $useSoftDeletes = false; // Linked to application status/deletion

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->db->fetch(
            'SELECT ai.*,
                    CONCAT(u.first_name, " ", u.last_name) AS conducted_by_name
             FROM adoption_interviews ai
             LEFT JOIN users u ON u.id = ai.conducted_by
             WHERE ai.id = :id
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function listByApplication(int $applicationId): array
    {
        return $this->db->fetchAll(
            'SELECT ai.*,
                    CONCAT(u.first_name, " ", u.last_name) AS conducted_by_name
             FROM adoption_interviews ai
             LEFT JOIN users u ON u.id = ai.conducted_by
             WHERE ai.application_id = :application_id
             ORDER BY ai.scheduled_date DESC, ai.id DESC',
            ['application_id' => $applicationId]
        );
    }
}
