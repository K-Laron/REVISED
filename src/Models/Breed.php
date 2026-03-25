<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Breed
{
    public function list(?string $species = null): array
    {
        $sql = 'SELECT id, species, name FROM breeds';
        $bindings = [];

        if ($species !== null && $species !== '') {
            $sql .= ' WHERE species = :species';
            $bindings['species'] = $species;
        }

        $sql .= ' ORDER BY species, name';

        return Database::fetchAll($sql, $bindings);
    }
}
