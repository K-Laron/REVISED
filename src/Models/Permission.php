<?php

declare(strict_types=1);

namespace App\Models;

class Permission extends BaseModel
{
    protected static string $table = 'permissions';
    protected static bool $useSoftDeletes = false; // Static system data

    public function namesForRole(int $roleId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT p.name
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id
             ORDER BY p.name',
            ['role_id' => $roleId]
        );

        return array_values(array_column($rows, 'name'));
    }

    public function getCatalog(): array
    {
        $rows = $this->db->fetchAll('SELECT * FROM permissions ORDER BY module ASC, display_name ASC, name ASC');
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row['module']][] = $row;
        }

        return $grouped;
    }
}
