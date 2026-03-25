<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Permission
{
    public function namesForRole(int $roleId): array
    {
        $rows = Database::fetchAll(
            'SELECT p.name
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id
             ORDER BY p.name',
            ['role_id' => $roleId]
        );

        return array_values(array_column($rows, 'name'));
    }
}
