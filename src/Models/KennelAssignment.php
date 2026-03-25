<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class KennelAssignment
{
    public function currentByKennel(int $kennelId): array
    {
        return Database::fetchAll(
            'SELECT ka.*, a.animal_id AS animal_code, a.name AS animal_name, a.species, a.size, a.status AS animal_status,
                    a.intake_date, ap.file_path AS primary_photo_path
             FROM kennel_assignments ka
             INNER JOIN animals a ON a.id = ka.animal_id
             LEFT JOIN animal_photos ap ON ap.animal_id = a.id AND ap.is_primary = 1
             WHERE ka.kennel_id = :kennel_id
               AND ka.released_at IS NULL
               AND a.is_deleted = 0
             ORDER BY ka.assigned_at ASC',
            ['kennel_id' => $kennelId]
        );
    }

    public function currentByKennelIds(array $kennelIds): array
    {
        if ($kennelIds === []) {
            return [];
        }

        $placeholders = [];
        $bindings = [];

        foreach (array_values($kennelIds) as $index => $kennelId) {
            $key = 'kennel_' . $index;
            $placeholders[] = ':' . $key;
            $bindings[$key] = $kennelId;
        }

        return Database::fetchAll(
            'SELECT ka.*, a.animal_id AS animal_code, a.name AS animal_name, a.species, a.size, a.status AS animal_status,
                    a.intake_date, ap.file_path AS primary_photo_path
             FROM kennel_assignments ka
             INNER JOIN animals a ON a.id = ka.animal_id
             LEFT JOIN animal_photos ap ON ap.animal_id = a.id AND ap.is_primary = 1
             WHERE ka.kennel_id IN (' . implode(', ', $placeholders) . ')
               AND ka.released_at IS NULL
               AND a.is_deleted = 0
             ORDER BY ka.assigned_at ASC',
            $bindings
        );
    }

    public function currentByAnimal(int $animalId): array|false
    {
        return Database::fetch(
            'SELECT ka.*, k.kennel_code, k.zone, k.status AS kennel_status
             FROM kennel_assignments ka
             INNER JOIN kennels k ON k.id = ka.kennel_id
             WHERE ka.animal_id = :animal_id
               AND ka.released_at IS NULL
             ORDER BY ka.assigned_at DESC
             LIMIT 1',
            ['animal_id' => $animalId]
        );
    }

    public function history(int $kennelId): array
    {
        return Database::fetchAll(
            'SELECT ka.*, a.animal_id AS animal_code, a.name AS animal_name, a.species, a.status AS animal_status
             FROM kennel_assignments ka
             INNER JOIN animals a ON a.id = ka.animal_id
             WHERE ka.kennel_id = :kennel_id
             ORDER BY ka.assigned_at DESC',
            ['kennel_id' => $kennelId]
        );
    }

    public function activeCount(int $kennelId): int
    {
        $row = Database::fetch(
            'SELECT COUNT(*) AS aggregate
             FROM kennel_assignments
             WHERE kennel_id = :kennel_id
               AND released_at IS NULL',
            ['kennel_id' => $kennelId]
        );

        return (int) ($row['aggregate'] ?? 0);
    }

    public function create(int $kennelId, int $animalId, ?int $assignedBy): int
    {
        Database::execute(
            'INSERT INTO kennel_assignments (kennel_id, animal_id, assigned_by)
             VALUES (:kennel_id, :animal_id, :assigned_by)',
            [
                'kennel_id' => $kennelId,
                'animal_id' => $animalId,
                'assigned_by' => $assignedBy,
            ]
        );

        return (int) Database::lastInsertId();
    }

    public function releaseByKennel(int $kennelId, ?int $releasedBy, ?string $reason): void
    {
        Database::execute(
            'UPDATE kennel_assignments
             SET released_at = NOW(),
                 released_by = :released_by,
                 transfer_reason = :transfer_reason
             WHERE kennel_id = :kennel_id
               AND released_at IS NULL',
            [
                'kennel_id' => $kennelId,
                'released_by' => $releasedBy,
                'transfer_reason' => $reason,
            ]
        );
    }

    public function releaseByAnimal(int $animalId, ?int $releasedBy, ?string $reason): void
    {
        Database::execute(
            'UPDATE kennel_assignments
             SET released_at = NOW(),
                 released_by = :released_by,
                 transfer_reason = :transfer_reason
             WHERE animal_id = :animal_id
               AND released_at IS NULL',
            [
                'animal_id' => $animalId,
                'released_by' => $releasedBy,
                'transfer_reason' => $reason,
            ]
        );
    }
}
