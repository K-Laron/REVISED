<?php

declare(strict_types=1);

namespace App\Models;

class User extends BaseModel
{
    protected static string $table = 'users';

    public function find(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->db->fetch(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
               AND (u.is_deleted = 0 OR :include_deleted = 1)
             LIMIT 1',
            ['id' => $id, 'include_deleted' => $includeDeleted ? 1 : 0]
        );
    }

    public function findById(int|string $id, bool $includeDeleted = false): array|false
    {
        return $this->find($id, $includeDeleted);
    }

    public function findByLoginIdentifier(string $identifier): array|false
    {
        return $this->db->fetch(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE (u.email = :email_identifier OR u.username = :username_identifier)
               AND u.is_deleted = 0
             LIMIT 1',
            [
                'email_identifier' => $identifier,
                'username_identifier' => $identifier,
            ]
        );
    }

    public function findByEmail(string $email): array|false
    {
        return $this->db->fetch(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
               AND u.is_deleted = 0
             LIMIT 1',
            ['email' => $email]
        );
    }

    public function findByUsername(string $username): array|false
    {
        return $this->db->fetch(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.username = :username
               AND u.is_deleted = 0
             LIMIT 1',
            ['username' => $username]
        );
    }

    public function permissions(int $userId): array
    {
        $rows = $this->db->fetchAll(
            'SELECT p.name
             FROM users u
             INNER JOIN role_permissions rp ON rp.role_id = u.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE u.id = :user_id
             ORDER BY p.name',
            ['user_id' => $userId]
        );

        return array_values(array_column($rows, 'name'));
    }

    public function incrementFailedLogin(int $userId, int $lockoutAttempts, int $lockoutMinutes): void
    {
        $user = $this->db->fetch('SELECT failed_login_attempts FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
        $attempts = ((int) ($user['failed_login_attempts'] ?? 0)) + 1;
        $lockedUntil = $attempts >= $lockoutAttempts
            ? date('Y-m-d H:i:s', time() + ($lockoutMinutes * 60))
            : null;

        $this->update($userId, [
            'failed_login_attempts' => $attempts,
            'locked_until' => $lockedUntil,
        ]);
    }

    public function clearFailedLogins(int $userId, string $ipAddress): void
    {
        $this->update($userId, [
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $ipAddress,
        ]);
    }

    public function storeSession(int $userId, string $tokenHash, string $ipAddress, string $userAgent, string $expiresAt): void
    {
        $this->db->execute(
            'INSERT INTO user_sessions (user_id, session_token_hash, ip_address, user_agent, expires_at)
             VALUES (:user_id, :token_hash, :ip_address, :user_agent, :expires_at)',
            [
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'ip_address' => $ipAddress,
                'user_agent' => mb_substr($userAgent, 0, 500),
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function deleteSession(string $tokenHash): void
    {
        $this->db->execute('DELETE FROM user_sessions WHERE session_token_hash = :token_hash', ['token_hash' => $tokenHash]);
    }

    public function sessionExists(string $tokenHash): bool
    {
        $row = $this->db->fetch(
            'SELECT id FROM user_sessions WHERE session_token_hash = :token_hash AND expires_at > NOW() LIMIT 1',
            ['token_hash' => $tokenHash]
        );

        return $row !== false;
    }

    public function updateProfile(int $userId, array $data): void
    {
        $this->update($userId, [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'middle_name' => $data['middle_name'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address_line1' => $data['address_line1'] ?? null,
            'address_line2' => $data['address_line2'] ?? null,
            'city' => $data['city'] ?? null,
            'province' => $data['province'] ?? null,
            'zip_code' => $data['zip_code'] ?? null,
        ]);
    }

    public function updatePassword(int $userId, string $passwordHash, bool $forcePasswordChange = false): void
    {
        $this->update($userId, [
            'password_hash' => $passwordHash,
            'force_password_change' => $forcePasswordChange ? 1 : 0,
        ]);
    }

    public function storePasswordResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        $this->db->execute(
            'INSERT INTO password_reset_tokens (user_id, token_hash, expires_at)
             VALUES (:user_id, :token_hash, :expires_at)',
            [
                'user_id' => $userId,
                'token_hash' => $tokenHash,
                'expires_at' => $expiresAt,
            ]
        );
    }

    public function findActiveResetToken(string $tokenHash): array|false
    {
        return $this->db->fetch(
            'SELECT prt.*, u.email
             FROM password_reset_tokens prt
             INNER JOIN users u ON u.id = prt.user_id
             WHERE prt.token_hash = :token_hash
               AND prt.used_at IS NULL
               AND prt.expires_at > NOW()
             LIMIT 1',
            ['token_hash' => $tokenHash]
        );
    }

    public function markResetTokenUsed(int $tokenId): void
    {
        $this->db->execute('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id', ['id' => $tokenId]);
    }

    public function invalidateSessions(int $userId): void
    {
        $this->db->execute('DELETE FROM user_sessions WHERE user_id = :user_id', ['user_id' => $userId]);
    }

    public function assignGeneratedUsername(int $userId): void
    {
        $this->db->execute(
            "UPDATE users u
             INNER JOIN roles r ON r.id = u.role_id
             SET u.username = CONCAT(r.name, '-', LPAD(u.id, 4, '0'))
             WHERE u.id = :id",
            ['id' => $userId]
        );
    }

    public function backfillGeneratedUsernames(): void
    {
        $this->db->execute(
            "UPDATE users u
             INNER JOIN roles r ON r.id = u.role_id
             SET u.username = CONCAT(r.name, '-', LPAD(u.id, 4, '0'))
             WHERE u.username IS NULL
                OR u.username = ''"
        );
    }

    public function getUsersByRole(string $roleName): array
    {
        return $this->db->fetchAll(
            'SELECT u.*
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE r.name = :role_name
               AND u.is_deleted = 0
               AND u.is_active = 1',
            ['role_name' => $roleName]
        );
    }

    public function getStaffOptions(): array
    {
        return $this->db->fetchAll(
            'SELECT u.id,
                    CONCAT(u.first_name, " ", u.last_name) AS full_name,
                    u.email,
                    r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_deleted = 0
               AND u.is_active = 1
             ORDER BY u.first_name ASC, u.last_name ASC'
        );
    }

    public function paginateUsers(array $filters, int $page, int $perPage): array
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
        $items = $this->db->fetchAll(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name,
                    CONCAT(cb.first_name, " ", cb.last_name) AS created_by_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN users cb ON cb.id = u.created_by
             ' . $where . '
             ORDER BY u.created_at DESC, u.id DESC
             LIMIT ' . (int) ($perPage + 1) . ' OFFSET ' . (int) $offset,
            $bindings
        );

        foreach ($items as &$item) {
            unset($item['password_hash']);
        }

        return [
            'items' => $items,
            'total_callback' => fn (): int => (int) ($this->db->fetch(
                'SELECT COUNT(*) AS aggregate
                 FROM users u
                 ' . $where,
                $bindings
            )['aggregate'] ?? 0),
        ];
    }

    public function getSessions(int $userId): array
    {
        return $this->db->fetchAll(
            'SELECT id, ip_address, user_agent, expires_at, last_activity_at, created_at
             FROM user_sessions
             WHERE user_id = :user_id
             ORDER BY last_activity_at DESC, id DESC',
            ['user_id' => $userId]
        );
    }

    public function findSession(int $sessionId, int $userId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM user_sessions WHERE id = :id LIMIT 1',
            ['id' => $sessionId]
        );
    }

    public function deleteSessionById(int $sessionId): void
    {
        $this->db->execute('DELETE FROM user_sessions WHERE id = :id', ['id' => $sessionId]);
    }

    public function listPractitioners(): array
    {
        return $this->db->fetchAll(
            'SELECT u.id, u.email, u.first_name, u.last_name, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_deleted = 0
               AND u.is_active = 1
             ORDER BY CASE WHEN r.name = "veterinarian" THEN 0 ELSE 1 END, u.first_name ASC, u.last_name ASC'
        );
    }

    public function isDefaultAdminActive(): bool
    {
        $user = $this->db->fetch(
            "SELECT password_hash FROM users WHERE email = 'admin@catarmanshelter.gov.ph' AND is_deleted = 0 LIMIT 1"
        );

        if ($user === false) {
            return false;
        }

        return password_verify('ChangeMe@2025', (string) ($user['password_hash'] ?? ''));
    }
}
