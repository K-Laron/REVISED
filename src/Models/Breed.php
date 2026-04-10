<?php

declare(strict_types=1);

namespace App\Models;

class Breed extends BaseModel
{
    protected static string $table = 'breeds';
    protected static bool $useSoftDeletes = false; // Static reference data

    public function list(?string $species = null): array
    {
        $sql = 'SELECT id, species, name FROM breeds';
        $bindings = [];

        if ($species !== null && $species !== '') {
            $sql .= ' WHERE species = :species';
            $bindings['species'] = $species;
        }

        $sql .= ' ORDER BY species, name';

        return $this->db->fetchAll($sql, $bindings);
    }
}
