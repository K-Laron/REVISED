<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Role
{
    public function findById(int $id): array|false
    {
        return Database::fetch('SELECT * FROM roles WHERE id = :id LIMIT 1', ['id' => $id]);
    }
}
