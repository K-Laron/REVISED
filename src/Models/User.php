<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO users (
                role_id, email, password_hash, first_name, last_name, middle_name, phone,
                address_line1, address_line2, city, province, zip_code, is_active,
                email_verified_at, force_password_change, created_by, updated_by
             ) VALUES (
                :role_id, :email, :password_hash, :first_name, :last_name, :middle_name, :phone,
                :address_line1, :address_line2, :city, :province, :zip_code, :is_active,
                :email_verified_at, :force_password_change, :created_by, :updated_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function findByLoginIdentifier(string $identifier): array|false
    {
        return Database::fetch(
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
        return Database::fetch(
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
        return Database::fetch(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.username = :username
               AND u.is_deleted = 0
             LIMIT 1',
            ['username' => $username]
        );
    }

    public function findById(int $id): array|false
    {
        return Database::fetch(
            'SELECT u.*, r.name AS role_name, r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id
               AND u.is_deleted = 0
             LIMIT 1',
            ['id' => $id]
        );
    }

    public function permissions(int $userId): array
    {
        $rows = Database::fetchAll(
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
        $user = Database::fetch('SELECT failed_login_attempts FROM users WHERE id = :id LIMIT 1', ['id' => $userId]);
        $attempts = ((int) ($user['failed_login_attempts'] ?? 0)) + 1;
        $lockedUntil = $attempts >= $lockoutAttempts
            ? date('Y-m-d H:i:s', time() + ($lockoutMinutes * 60))
            : null;

        Database::execute(
            'UPDATE users
             SET failed_login_attempts = :attempts,
                 locked_until = :locked_until
             WHERE id = :id',
            [
                'id' => $userId,
                'attempts' => $attempts,
                'locked_until' => $lockedUntil,
            ]
        );
    }

    public function clearFailedLogins(int $userId, string $ipAddress): void
    {
        Database::execute(
            'UPDATE users
             SET failed_login_attempts = 0,
                 locked_until = NULL,
                 last_login_at = NOW(),
                 last_login_ip = :ip_address
             WHERE id = :id',
            ['id' => $userId, 'ip_address' => $ipAddress]
        );
    }

    public function storeSession(int $userId, string $tokenHash, string $ipAddress, string $userAgent, string $expiresAt): void
    {
        Database::execute(
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
        Database::execute('DELETE FROM user_sessions WHERE session_token_hash = :token_hash', ['token_hash' => $tokenHash]);
    }

    public function sessionExists(string $tokenHash): bool
    {
        $row = Database::fetch(
            'SELECT id FROM user_sessions WHERE session_token_hash = :token_hash AND expires_at > NOW() LIMIT 1',
            ['token_hash' => $tokenHash]
        );

        return $row !== false;
    }

    public function updateProfile(int $userId, array $data): void
    {
        Database::execute(
            'UPDATE users
             SET first_name = :first_name,
                 last_name = :last_name,
                 middle_name = :middle_name,
                 phone = :phone,
                 address_line1 = :address_line1,
                 address_line2 = :address_line2,
                 city = :city,
                 province = :province,
                 zip_code = :zip_code
             WHERE id = :id',
            [
                'id' => $userId,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'middle_name' => $data['middle_name'] ?? null,
                'phone' => $data['phone'] ?? null,
                'address_line1' => $data['address_line1'] ?? null,
                'address_line2' => $data['address_line2'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? null,
                'zip_code' => $data['zip_code'] ?? null,
            ]
        );
    }

    public function updatePassword(int $userId, string $passwordHash, bool $forcePasswordChange = false): void
    {
        Database::execute(
            'UPDATE users
             SET password_hash = :password_hash,
                 force_password_change = :force_password_change
             WHERE id = :id',
            [
                'id' => $userId,
                'password_hash' => $passwordHash,
                'force_password_change' => $forcePasswordChange ? 1 : 0,
            ]
        );
    }

    public function storePasswordResetToken(int $userId, string $tokenHash, string $expiresAt): void
    {
        Database::execute(
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
        return Database::fetch(
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
        Database::execute('UPDATE password_reset_tokens SET used_at = NOW() WHERE id = :id', ['id' => $tokenId]);
    }

    public function invalidateSessions(int $userId): void
    {
        Database::execute('DELETE FROM user_sessions WHERE user_id = :user_id', ['user_id' => $userId]);
    }

    public function assignGeneratedUsername(int $userId): void
    {
        Database::execute(
            "UPDATE users u
             INNER JOIN roles r ON r.id = u.role_id
             SET u.username = CONCAT(r.name, '-', LPAD(u.id, 4, '0'))
             WHERE u.id = :id",
            ['id' => $userId]
        );
    }

    public function backfillGeneratedUsernames(): void
    {
        Database::execute(
            "UPDATE users u
             INNER JOIN roles r ON r.id = u.role_id
             SET u.username = CONCAT(r.name, '-', LPAD(u.id, 4, '0'))
             WHERE u.username IS NULL
                OR u.username = ''"
        );
    }
}
