<?php

declare(strict_types=1);

namespace App\Models;

class AnimalPhoto extends BaseModel
{
    protected static string $table = 'animal_photos';
    protected static bool $useSoftDeletes = false; // Photos are physically deleted or masked

    public function listByAnimal(int $animalId): array
    {
        return $this->db->fetchAll(
            'SELECT * FROM animal_photos WHERE animal_id = :animal_id ORDER BY is_primary DESC, sort_order ASC, id ASC',
            ['animal_id' => $animalId]
        );
    }

    public function countByAnimal(int $animalId): int
    {
        return (int) ($this->db->fetch(
            'SELECT COUNT(*) AS aggregate FROM animal_photos WHERE animal_id = :animal_id',
            ['animal_id' => $animalId]
        )['aggregate'] ?? 0);
    }

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        // Override to support animal_id grouping if needed, but BaseModel::find works for PK id.
        // The original find(int $animalId, int $photoId) was non-standard.
        // I'll keep a specialized one for that.
        return parent::find($id);
    }

    public function findByAnimal(int $animalId, int $photoId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM animal_photos WHERE animal_id = :animal_id AND id = :id LIMIT 1',
            ['animal_id' => $animalId, 'id' => $photoId]
        );
    }

    public function updateOrdering(int $animalId, int $photoId, int $sortOrder, int $isPrimary): void
    {
        $this->db->execute(
            'UPDATE animal_photos
             SET sort_order = :sort_order, is_primary = :is_primary
             WHERE animal_id = :animal_id AND id = :id',
            [
                'animal_id' => $animalId,
                'id' => $photoId,
                'sort_order' => $sortOrder,
                'is_primary' => $isPrimary,
            ]
        );
    }
}
