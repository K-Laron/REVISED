<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Session;
use App\Helpers\Sanitizer;
use App\Middleware\CsrfMiddleware;
use App\Models\User;
use App\Support\SystemSettings;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class AuthService
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function attemptLogin(string $identifier, string $password, Request $request): array
    {
        $identifier = $this->normalizeLoginIdentifier($identifier);
        $user = $this->users->findByLoginIdentifier($identifier);
        $config = require dirname(__DIR__, 2) . '/config/auth.php';

        if ($user === false) {
            $this->logAudit(null, 'failed_login', 'auth', null, ['identifier' => $identifier], $request);
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        if ((int) $user['is_active'] !== 1 || (int) $user['is_deleted'] === 1) {
            return ['success' => false, 'message' => 'Your account is inactive.'];
        }

        if ($user['locked_until'] !== null && strtotime((string) $user['locked_until']) > time()) {
            return ['success' => false, 'message' => 'Your account is temporarily locked. Please try again later.'];
        }

        if (!password_verify($password, (string) $user['password_hash'])) {
            $this->users->incrementFailedLogin((int) $user['id'], (int) $config['lockout_attempts'], (int) $config['lockout_minutes']);
            $this->logAudit((int) $user['id'], 'failed_login', 'auth', (int) $user['id'], ['identifier' => $identifier], $request);
            return ['success' => false, 'message' => 'Invalid credentials.'];
        }

        $this->users->clearFailedLogins((int) $user['id'], $request->ip());
        Session::regenerate();

        $user = $this->hydrateUser((int) $user['id']);
        $sessionToken = bin2hex(random_bytes(32));
        $sessionTokenHash = hash('sha256', $sessionToken);
        $expiresAt = date('Y-m-d H:i:s', time() + ((int) ($_ENV['SESSION_LIFETIME'] ?? 120) * 60));

        $this->users->storeSession((int) $user['id'], $sessionTokenHash, $request->ip(), $request->userAgent(), $expiresAt);

        Session::put('auth.user', $user);
        Session::put('auth.session_token', $sessionToken);
        CsrfMiddleware::rotateToken();

        $this->logAudit((int) $user['id'], 'login', 'auth', (int) $user['id'], [
            'email' => $user['email'],
            'username' => $user['username'] ?? null,
        ], $request);

        return [
            'success' => true,
            'user' => $user,
        ];
    }

    public function logout(Request $request): void
    {
        $user = Session::get('auth.user');
        $sessionToken = (string) Session::get('auth.session_token', '');

        if ($sessionToken !== '') {
            $this->users->deleteSession(hash('sha256', $sessionToken));
        }

        if (is_array($user)) {
            $this->logAudit((int) $user['id'], 'logout', 'auth', (int) $user['id'], [], $request);
        }

        Session::destroy();
    }

    public function userFromRequest(Request $request): ?array
    {
        $sessionUser = Session::get('auth.user');
        $sessionToken = (string) Session::get('auth.session_token', '');

        if (is_array($sessionUser) && $sessionToken !== '') {
            try {
                if ($this->users->sessionExists(hash('sha256', $sessionToken))) {
                    return $sessionUser;
                }

                Session::clearAuthState();
            } catch (Throwable) {
                Session::clearAuthState();
                return null;
            }
        }

        return null;
    }

    public function updateProfile(int $userId, array $data): array
    {
        $data['phone'] = Sanitizer::phone($data['phone'] ?? null);
        $this->users->updateProfile($userId, $data);
        $user = $this->hydrateUser($userId);
        Session::put('auth.user', $user);

        return $user;
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $user = $this->users->findById($userId);
        if ($user === false || !password_verify($currentPassword, (string) $user['password_hash'])) {
            return false;
        }

        $this->users->updatePassword($userId, password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]));
        $this->users->invalidateSessions($userId);
        Session::forget('_csrf_token');
        Session::destroy();

        return true;
    }

    public function createPasswordReset(string $email): void
    {
        $user = $this->users->findByEmail(Sanitizer::email($email) ?? '');
        if ($user === false) {
            return;
        }

        $plainToken = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $plainToken);
        $expiresAt = date('Y-m-d H:i:s', time() + (15 * 60));

        $this->users->storePasswordResetToken((int) $user['id'], $tokenHash, $expiresAt);
        $resetUrl = rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost:8000'), '/') . '/reset-password/' . $plainToken;

        $this->sendResetEmail((string) $user['email'], $resetUrl);
        Logger::info('Password reset token created.', ['email' => $user['email'], 'reset_url' => $resetUrl]);
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $tokenHash = hash('sha256', $token);
        $resetToken = $this->users->findActiveResetToken($tokenHash);

        if ($resetToken === false) {
            return false;
        }

        $this->users->updatePassword((int) $resetToken['user_id'], password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]));
        $this->users->markResetTokenUsed((int) $resetToken['id']);
        $this->users->invalidateSessions((int) $resetToken['user_id']);
        CsrfMiddleware::rotateToken();

        return true;
    }

    private function hydrateUser(int $userId): array
    {
        $user = $this->users->findById($userId);
        $user['permissions'] = $this->users->permissions($userId);
        unset($user['password_hash']);

        return $user;
    }

    private function normalizeLoginIdentifier(string $value): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) !== false) {
            return Sanitizer::email($trimmed) ?? '';
        }

        return Sanitizer::username($trimmed) ?? '';
    }

    private function sendResetEmail(string $email, string $resetUrl): void
    {
        $mailMode = (string) SystemSettings::get('mail_delivery_mode', 'log_only');
        if ($mailMode !== 'smtp') {
            Logger::info('Password reset email delivery skipped.', [
                'email' => $email,
                'mode' => $mailMode,
                'reset_url' => $resetUrl,
            ]);

            return;
        }

        $mailConfig = require dirname(__DIR__, 2) . '/config/mail.php';
        if (($mailConfig['host'] ?? '') === '' || ($mailConfig['username'] ?? '') === '') {
            return;
        }

        try {
            $mailer = new PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = $mailConfig['host'];
            $mailer->Port = (int) $mailConfig['port'];
            $mailer->SMTPAuth = true;
            $mailer->Username = $mailConfig['username'];
            $mailer->Password = $mailConfig['password'];
            $mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->setFrom($mailConfig['from_address'], $mailConfig['from_name']);
            $mailer->addAddress($email);
            $mailer->Subject = 'Reset your Catarman Animal Shelter password';
            $mailer->Body = "Open this link to reset your password:\n\n{$resetUrl}";
            $mailer->send();
        } catch (Throwable $exception) {
            Logger::warning('Password reset email failed to send.', [
                'email' => $email,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function logAudit(?int $userId, string $action, string $module, ?int $recordId, array $newValues, Request $request): void
    {
        try {
            Database::execute(
                'INSERT INTO audit_logs (user_id, action, module, record_table, record_id, new_values, ip_address, user_agent, request_id)
                 VALUES (:user_id, :action, :module, :record_table, :record_id, :new_values, :ip_address, :user_agent, :request_id)',
                [
                    'user_id' => $userId,
                    'action' => $action,
                    'module' => $module,
                    'record_table' => 'users',
                    'record_id' => $recordId,
                    'new_values' => json_encode($newValues, JSON_UNESCAPED_SLASHES),
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr($request->userAgent(), 0, 500),
                    'request_id' => bin2hex(random_bytes(16)),
                ]
            );
        } catch (Throwable $exception) {
            Logger::warning('Audit log insert failed.', ['error' => $exception->getMessage()]);
        }
    }
}
