<?php

declare(strict_types=1);

namespace App\Models;

class Role extends BaseModel
{
    protected static string $table = 'roles';
    protected static bool $useSoftDeletes = false; // Static system data
    
    public function findByName(string $name): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM roles WHERE name = :name LIMIT 1',
            ['name' => $name]
        );
    }

    public function listWithUserCounts(): array
    {
        return $this->db->fetchAll(
            'SELECT r.*, COUNT(u.id) AS user_count
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id AND u.is_deleted = 0
             GROUP BY r.id
             ORDER BY r.display_name ASC'
        );
    }

    public function deleteRolePermissions(int $roleId): void
    {
        $this->db->execute('DELETE FROM role_permissions WHERE role_id = :role_id', ['role_id' => $roleId]);
    }

    public function addRolePermission(int $roleId, int $permissionId): void
    {
        $this->db->execute(
            'INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)',
            ['role_id' => $roleId, 'permission_id' => $permissionId]
        );
    }

    public function invalidateSessionsForRole(int $roleId): void
    {
        $this->db->execute(
            'DELETE FROM user_sessions WHERE user_id IN (SELECT id FROM users WHERE role_id = :role_id)',
            ['role_id' => $roleId]
        );
    }
}
