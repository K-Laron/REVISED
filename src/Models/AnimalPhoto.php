<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class AnimalPhoto
{
    public function listByAnimal(int $animalId): array
    {
        return Database::fetchAll(
            'SELECT * FROM animal_photos WHERE animal_id = :animal_id ORDER BY is_primary DESC, sort_order ASC, id ASC',
            ['animal_id' => $animalId]
        );
    }

    public function countByAnimal(int $animalId): int
    {
        return (int) (Database::fetch(
            'SELECT COUNT(*) AS aggregate FROM animal_photos WHERE animal_id = :animal_id',
            ['animal_id' => $animalId]
        )['aggregate'] ?? 0);
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO animal_photos (animal_id, file_path, file_name, file_size_bytes, mime_type, is_primary, sort_order, uploaded_by)
             VALUES (:animal_id, :file_path, :file_name, :file_size_bytes, :mime_type, :is_primary, :sort_order, :uploaded_by)',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function find(int $animalId, int $photoId): array|false
    {
        return Database::fetch(
            'SELECT * FROM animal_photos WHERE animal_id = :animal_id AND id = :id LIMIT 1',
            ['animal_id' => $animalId, 'id' => $photoId]
        );
    }

    public function delete(int $photoId): void
    {
        Database::execute('DELETE FROM animal_photos WHERE id = :id', ['id' => $photoId]);
    }

    public function updateOrdering(int $animalId, int $photoId, int $sortOrder, int $isPrimary): void
    {
        Database::execute(
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
