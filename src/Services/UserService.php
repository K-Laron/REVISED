<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Request;
use App\Helpers\Sanitizer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use RuntimeException;

class UserService
{
    private User $users;
    private Role $roles;
    private Permission $permissions;
    private AuditService $audit;
    private NotificationService $notifications;

    public function __construct()
    {
        $this->users = new User();
        $this->roles = new Role();
        $this->permissions = new Permission();
        $this->audit = new AuditService();
        $this->notifications = new NotificationService();
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;
        $conditions = [];
        $bindings = [];

        $showDeleted = ($filters['tab'] ?? 'active') === 'deleted';
        $conditions[] = 'u.is_deleted = :is_deleted';
        $bindings['is_deleted'] = $showDeleted ? 1 : 0;

        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(u.username LIKE :search OR u.email LIKE :search OR u.first_name LIKE :search OR u.last_name LIKE :search)';
            $bindings['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (($filters['role_id'] ?? '') !== '') {
            $conditions[] = 'u.role_id = :role_id';
            $bindings['role_id'] = (int) $filters['role_id'];
        }

        if (($filters['status'] ?? '') !== '') {
            if ($filters['status'] === 'active') {
                $conditions[] = 'u.is_active = 1';
            } elseif ($filters['status'] === 'inactive') {
                $conditions[] = 'u.is_active = 0';
            }
        }

        $where = 'WHERE ' . implode(' AND ', $conditions);
        $total = (int) (Database::fetch(
            'SELECT COUNT(*) AS aggregate
             FROM users u
             ' . $where,
            $bindings
        )['aggregate'] ?? 0);

        $items = Database::fetchAll(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name,
                    CONCAT(cb.first_name, " ", cb.last_name) AS created_by_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN users cb ON cb.id = u.created_by
             ' . $where . '
             ORDER BY u.created_at DESC, u.id DESC
             LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset,
            $bindings
        );

        foreach ($items as &$item) {
            unset($item['password_hash']);
        }

        return ['items' => $items, 'total' => $total];
    }

    public function get(int $userId, bool $includeDeleted = true): array
    {
        $conditions = $includeDeleted ? '' : ' AND u.is_deleted = 0';
        $user = Database::fetch(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id' . $conditions . '
             LIMIT 1',
            ['id' => $userId]
        );

        if ($user === false) {
            throw new RuntimeException('User not found.');
        }

        $user['permissions'] = $this->users->permissions($userId);
        $user['sessions'] = $this->sessions($userId);
        unset($user['password_hash']);

        return $user;
    }

    public function roles(): array
    {
        return Database::fetchAll(
            'SELECT r.*, COUNT(u.id) AS user_count
             FROM roles r
             LEFT JOIN users u ON u.role_id = r.id AND u.is_deleted = 0
             GROUP BY r.id
             ORDER BY r.display_name ASC'
        );
    }

    public function permissionCatalog(): array
    {
        $rows = Database::fetchAll('SELECT * FROM permissions ORDER BY module ASC, display_name ASC, name ASC');
        $grouped = [];

        foreach ($rows as $row) {
            $grouped[$row['module']][] = $row;
        }

        return $grouped;
    }

    public function rolePermissions(int $roleId): array
    {
        return $this->permissions->namesForRole($roleId);
    }

    public function create(array $data, int $actorId, Request $request): array
    {
        $email = Sanitizer::email((string) $data['email']);
        if ($email === null) {
            throw new RuntimeException('A valid email address is required.');
        }

        if ($this->users->findByEmail($email) !== false) {
            throw new RuntimeException('Email address is already in use.');
        }

        $payload = [
            'role_id' => (int) $data['role_id'],
            'email' => $email,
            'password_hash' => password_hash((string) $data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'middle_name' => $this->nullIfBlank($data['middle_name'] ?? null),
            'phone' => Sanitizer::phone($data['phone'] ?? null),
            'address_line1' => $this->nullIfBlank($data['address_line1'] ?? null),
            'address_line2' => $this->nullIfBlank($data['address_line2'] ?? null),
            'city' => $this->nullIfBlank($data['city'] ?? null),
            'province' => $this->nullIfBlank($data['province'] ?? null),
            'zip_code' => $this->nullIfBlank($data['zip_code'] ?? null),
            'is_active' => $this->truthy($data['is_active'] ?? true) ? 1 : 0,
            'email_verified_at' => $this->truthy($data['email_verified'] ?? false) ? date('Y-m-d H:i:s') : null,
            'force_password_change' => $this->truthy($data['force_password_change'] ?? true) ? 1 : 0,
            'created_by' => $actorId,
            'updated_by' => $actorId,
        ];

        $userId = $this->users->create($payload);
        $this->users->assignGeneratedUsername($userId);
        $user = $this->get($userId);

        $this->audit->record($actorId, 'create', 'users', 'users', $userId, [], $user, $request);
        $this->notifications->create([
            'user_id' => $userId,
            'type' => 'account',
            'title' => 'Your account is ready',
            'message' => 'A shelter account has been created for you. Your username is ' . ($user['username'] ?? '') . '. Sign in using your assigned password.',
            'link' => '/login',
        ]);

        return $user;
    }

    public function update(int $userId, array $data, int $actorId, Request $request): array
    {
        $current = $this->get($userId);
        $email = Sanitizer::email((string) $data['email']);
        if ($email === null) {
            throw new RuntimeException('A valid email address is required.');
        }

        $existing = $this->users->findByEmail($email);
        if ($existing !== false && (int) $existing['id'] !== $userId) {
            throw new RuntimeException('Email address is already in use.');
        }

        if ((int) $current['id'] === $actorId && !$this->truthy($data['is_active'] ?? true)) {
            throw new RuntimeException('You cannot deactivate your own account.');
        }

        Database::execute(
            'UPDATE users
             SET role_id = :role_id,
                 email = :email,
                 first_name = :first_name,
                 last_name = :last_name,
                 middle_name = :middle_name,
                 phone = :phone,
                 address_line1 = :address_line1,
                 address_line2 = :address_line2,
                 city = :city,
                 province = :province,
                 zip_code = :zip_code,
                 is_active = :is_active,
                 email_verified_at = :email_verified_at,
                 force_password_change = :force_password_change,
                 updated_by = :updated_by
             WHERE id = :id',
            [
                'id' => $userId,
                'role_id' => (int) $data['role_id'],
                'email' => $email,
                'first_name' => trim((string) $data['first_name']),
                'last_name' => trim((string) $data['last_name']),
                'middle_name' => $this->nullIfBlank($data['middle_name'] ?? null),
                'phone' => Sanitizer::phone($data['phone'] ?? null),
                'address_line1' => $this->nullIfBlank($data['address_line1'] ?? null),
                'address_line2' => $this->nullIfBlank($data['address_line2'] ?? null),
                'city' => $this->nullIfBlank($data['city'] ?? null),
                'province' => $this->nullIfBlank($data['province'] ?? null),
                'zip_code' => $this->nullIfBlank($data['zip_code'] ?? null),
                'is_active' => $this->truthy($data['is_active'] ?? true) ? 1 : 0,
                'email_verified_at' => $this->truthy($data['email_verified'] ?? false) ? ($current['email_verified_at'] ?: date('Y-m-d H:i:s')) : null,
                'force_password_change' => $this->truthy($data['force_password_change'] ?? false) ? 1 : 0,
                'updated_by' => $actorId,
            ]
        );

        if ((int) $current['role_id'] !== (int) $data['role_id']) {
            $this->users->assignGeneratedUsername($userId);
            $this->users->invalidateSessions($userId);
        }

        $user = $this->get($userId);
        $this->audit->record($actorId, 'update', 'users', 'users', $userId, $current, $user, $request);

        return $user;
    }

    public function delete(int $userId, int $actorId, Request $request): void
    {
        if ($userId === $actorId) {
            throw new RuntimeException('You cannot delete your own account.');
        }

        $current = $this->get($userId);
        Database::execute(
            'UPDATE users
             SET is_deleted = 1,
                 is_active = 0,
                 deleted_at = NOW(),
                 deleted_by = :deleted_by,
                 updated_by = :updated_by
             WHERE id = :id',
            ['id' => $userId, 'deleted_by' => $actorId, 'updated_by' => $actorId]
        );
        $this->users->invalidateSessions($userId);
        $this->audit->record($actorId, 'delete', 'users', 'users', $userId, $current, ['is_deleted' => true], $request);
    }

    public function restore(int $userId, int $actorId, Request $request): array
    {
        Database::execute(
            'UPDATE users
             SET is_deleted = 0,
                 is_active = 1,
                 deleted_at = NULL,
                 deleted_by = NULL,
                 updated_by = :updated_by
             WHERE id = :id',
            ['id' => $userId, 'updated_by' => $actorId]
        );

        $user = $this->get($userId);
        $this->audit->record($actorId, 'restore', 'users', 'users', $userId, ['is_deleted' => true], $user, $request);

        return $user;
    }

    public function changeRole(int $userId, int $roleId, int $actorId, Request $request): array
    {
        $role = $this->roles->findById($roleId);
        if ($role === false) {
            throw new RuntimeException('Role not found.');
        }

        $current = $this->get($userId);

        Database::execute(
            'UPDATE users SET role_id = :role_id, updated_by = :updated_by WHERE id = :id',
            ['id' => $userId, 'role_id' => $roleId, 'updated_by' => $actorId]
        );
        $this->users->assignGeneratedUsername($userId);
        $this->users->invalidateSessions($userId);

        $user = $this->get($userId);
        $this->audit->record(
            $actorId,
            'update',
            'users',
            'users',
            $userId,
            ['role_id' => $current['role_id'], 'username' => $current['username'] ?? null],
            ['role_id' => $roleId, 'username' => $user['username'] ?? null],
            $request
        );
        $this->notifications->create([
            'user_id' => $userId,
            'type' => 'account',
            'title' => 'Role updated',
            'message' => 'Your account role has been updated to ' . $role['display_name'] . '. Your username is ' . ($user['username'] ?? '') . '.',
            'link' => '/dashboard',
        ]);

        return $user;
    }

    public function resetPassword(int $userId, string $password, int $actorId, Request $request): void
    {
        $this->get($userId);
        $this->users->updatePassword($userId, password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), true);
        $this->users->invalidateSessions($userId);
        $this->audit->record($actorId, 'update', 'users', 'users', $userId, [], ['password_reset' => true], $request);
        $this->notifications->create([
            'user_id' => $userId,
            'type' => 'account',
            'title' => 'Password reset',
            'message' => 'Your shelter account password was reset by an administrator.',
            'link' => '/login',
        ]);
    }

    public function sessions(int $userId): array
    {
        return Database::fetchAll(
            'SELECT id, ip_address, user_agent, expires_at, last_activity_at, created_at
             FROM user_sessions
             WHERE user_id = :user_id
             ORDER BY last_activity_at DESC, id DESC',
            ['user_id' => $userId]
        );
    }

    public function destroySession(int $userId, int $sessionId, int $actorId, Request $request): void
    {
        $session = Database::fetch(
            'SELECT id, user_id, ip_address, user_agent, expires_at
             FROM user_sessions
             WHERE id = :id AND user_id = :user_id
             LIMIT 1',
            ['id' => $sessionId, 'user_id' => $userId]
        );

        if ($session === false) {
            throw new RuntimeException('Session not found.');
        }

        Database::execute('DELETE FROM user_sessions WHERE id = :id', ['id' => $sessionId]);
        $this->audit->record($actorId, 'delete', 'users', 'user_sessions', $sessionId, $session, [], $request);
    }

    public function updateRolePermissions(int $roleId, array $permissionIds, int $actorId, Request $request): array
    {
        $role = $this->roles->findById($roleId);
        if ($role === false) {
            throw new RuntimeException('Role not found.');
        }

        $previous = $this->rolePermissions($roleId);
        $cleanIds = array_values(array_unique(array_map('intval', $permissionIds)));

        Database::beginTransaction();

        try {
            Database::execute('DELETE FROM role_permissions WHERE role_id = :role_id', ['role_id' => $roleId]);

            foreach ($cleanIds as $permissionId) {
                Database::execute(
                    'INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)',
                    ['role_id' => $roleId, 'permission_id' => $permissionId]
                );
            }

            Database::execute('DELETE FROM user_sessions WHERE user_id IN (SELECT id FROM users WHERE role_id = :role_id)', ['role_id' => $roleId]);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->rolePermissions($roleId);
        $this->audit->record($actorId, 'update', 'users', 'role_permissions', $roleId, ['permissions' => $previous], ['permissions' => $updated], $request);

        return [
            'role' => $role,
            'permissions' => $updated,
        ];
    }

    private function truthy(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function nullIfBlank(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
