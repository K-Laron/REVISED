<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Helpers\Sanitizer;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Support\InputNormalizer;
use App\Support\Pagination\PaginatedWindow;
use RuntimeException;

class UserService
{
    public function __construct(
        private readonly User $users,
        private readonly Role $roles,
        private readonly Permission $permissions,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications
    ) {
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        $result = $this->users->paginateUsers($filters, $page, $perPage);

        $window = PaginatedWindow::resolve(
            $result['items'],
            $page,
            $perPage,
            $result['total_callback']
        );

        return ['items' => $window['items'], 'total' => $window['total']];
    }

    public function get(int $userId, bool $includeDeleted = true): array
    {
        $user = $this->users->find($userId, $includeDeleted);

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
        return $this->roles->listWithUserCounts();
    }

    public function permissionCatalog(): array
    {
        return $this->permissions->getCatalog();
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
            'middle_name' => InputNormalizer::nullIfBlank($data['middle_name'] ?? null),
            'phone' => Sanitizer::phone($data['phone'] ?? null),
            'address_line1' => InputNormalizer::nullIfBlank($data['address_line1'] ?? null),
            'address_line2' => InputNormalizer::nullIfBlank($data['address_line2'] ?? null),
            'city' => InputNormalizer::nullIfBlank($data['city'] ?? null),
            'province' => InputNormalizer::nullIfBlank($data['province'] ?? null),
            'zip_code' => InputNormalizer::nullIfBlank($data['zip_code'] ?? null),
            'is_active' => InputNormalizer::bool($data['is_active'] ?? true) ? 1 : 0,
            'email_verified_at' => InputNormalizer::bool($data['email_verified'] ?? false) ? date('Y-m-d H:i:s') : null,
            'force_password_change' => InputNormalizer::bool($data['force_password_change'] ?? true) ? 1 : 0,
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

        $this->notifications->notifyRole('super_admin', [
            'type' => 'info',
            'title' => 'New User Account Created',
            'message' => 'A new internal user account for ' . ($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '') . ' was successfully created.',
            'link' => '/users/' . $userId,
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

        if ((int) $current['id'] === $actorId && !InputNormalizer::bool($data['is_active'] ?? true)) {
            throw new RuntimeException('You cannot deactivate your own account.');
        }

        $this->users->update($userId, [
            'role_id' => (int) $data['role_id'],
            'email' => $email,
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
            'middle_name' => InputNormalizer::nullIfBlank($data['middle_name'] ?? null),
            'phone' => Sanitizer::phone($data['phone'] ?? null),
            'address_line1' => InputNormalizer::nullIfBlank($data['address_line1'] ?? null),
            'address_line2' => InputNormalizer::nullIfBlank($data['address_line2'] ?? null),
            'city' => InputNormalizer::nullIfBlank($data['city'] ?? null),
            'province' => InputNormalizer::nullIfBlank($data['province'] ?? null),
            'zip_code' => InputNormalizer::nullIfBlank($data['zip_code'] ?? null),
            'is_active' => InputNormalizer::bool($data['is_active'] ?? true) ? 1 : 0,
            'email_verified_at' => InputNormalizer::bool($data['email_verified'] ?? false) ? ($current['email_verified_at'] ?: date('Y-m-d H:i:s')) : null,
            'force_password_change' => InputNormalizer::bool($data['force_password_change'] ?? false) ? 1 : 0,
            'updated_by' => $actorId,
        ]);

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
        $this->users->update($userId, [
            'is_deleted' => 1,
            'is_active' => 0,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $actorId,
            'updated_by' => $actorId
        ]);
        $this->users->invalidateSessions($userId);
        $this->audit->record($actorId, 'delete', 'users', 'users', $userId, $current, ['is_deleted' => true], $request);
    }

    public function restore(int $userId, int $actorId, Request $request): array
    {
        $this->users->update($userId, [
            'is_deleted' => 0,
            'is_active' => 1,
            'deleted_at' => null,
            'deleted_by' => null,
            'updated_by' => $actorId
        ]);

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

        $this->users->update($userId, ['role_id' => $roleId, 'updated_by' => $actorId]);
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
        return $this->users->getSessions($userId);
    }

    public function destroySession(int $userId, int $sessionId, int $actorId, Request $request): void
    {
        $session = $this->users->findSession($sessionId, $userId);

        if ($session === false) {
            throw new RuntimeException('Session not found.');
        }

        $this->users->deleteSessionById($sessionId);
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

        $this->roles->db->beginTransaction();

        try {
            $this->roles->deleteRolePermissions($roleId);

            foreach ($cleanIds as $permissionId) {
                $this->roles->addRolePermission($roleId, $permissionId);
            }

            $this->roles->invalidateSessionsForRole($roleId);
            $this->roles->db->commit();
        } catch (\Throwable $exception) {
            $this->roles->db->rollBack();
            throw $exception;
        }

        $updated = $this->rolePermissions($roleId);
        $this->audit->record($actorId, 'update', 'users', 'role_permissions', $roleId, ['permissions' => $previous], ['permissions' => $updated], $request);

        return [
            'role' => $role,
            'permissions' => $updated,
        ];
    }
}
