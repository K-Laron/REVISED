<?php

declare(strict_types=1);

namespace App\Models;

class AdoptionCompletion extends BaseModel
{
    protected static string $table = 'adoption_completions';
    protected static bool $useSoftDeletes = false; // Linked to application status/deletion

    public function findByApplication(int $applicationId): array|false
    {
        return $this->db->fetch(
            'SELECT ac.*,
                    CONCAT(u.first_name, " ", u.last_name) AS processed_by_name
             FROM adoption_completions ac
             LEFT JOIN users u ON u.id = ac.processed_by
             WHERE ac.application_id = :application_id
             LIMIT 1',
            ['application_id' => $applicationId]
        );
    }

    public function updateCertificatePath(int $id, string $certificatePath): void
    {
        $this->update($id, [
            'certificate_path' => $certificatePath,
        ]);
    }

    public function findByAnimal(int $animalId): array|false
    {
        return $this->db->fetch(
            'SELECT ac.*, CONCAT(u.first_name, " ", u.last_name) AS processed_by_name
             FROM adoption_completions ac
             LEFT JOIN users u ON u.id = ac.processed_by
             WHERE ac.animal_id = :animal_id
             LIMIT 1',
            ['animal_id' => $animalId]
        );
    }
}
