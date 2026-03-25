<?php

declare(strict_types=1);

namespace App\Support;

class LandingPage
{
    public static function forUser(?array $user): string
    {
        if (!is_array($user)) {
            return '/login';
        }

        if ((int) ($user['force_password_change'] ?? 0) === 1) {
            return '/force-password-change';
        }

        $roleName = (string) ($user['role_name'] ?? '');
        $roleLandingMap = [
            'adopter' => '/adopt/apply',
            'super_admin' => '/dashboard',
            'shelter_head' => '/dashboard',
            'veterinarian' => '/medical',
            'shelter_staff' => '/animals',
            'billing_clerk' => '/billing',
        ];

        if (isset($roleLandingMap[$roleName])) {
            return $roleLandingMap[$roleName];
        }

        return match (true) {
            in_array('animals.read', $user['permissions'] ?? [], true) => '/animals',
            in_array('medical.read', $user['permissions'] ?? [], true) => '/medical',
            in_array('kennels.read', $user['permissions'] ?? [], true) => '/kennels',
            in_array('adoptions.read', $user['permissions'] ?? [], true) => '/adoptions',
            in_array('billing.read', $user['permissions'] ?? [], true) => '/billing',
            in_array('inventory.read', $user['permissions'] ?? [], true) => '/inventory',
            in_array('users.read', $user['permissions'] ?? [], true) => '/users',
            in_array('reports.read', $user['permissions'] ?? [], true) => '/reports',
            default => '/settings',
        };
    }

    public static function actionForUser(?array $user): array
    {
        $href = self::forUser($user);

        $label = match ($href) {
            '/dashboard' => 'Back to dashboard',
            '/adopt/apply' => 'Back to my adoption',
            '/login' => 'Sign in',
            default => 'Open system',
        };

        return [
            'href' => $href,
            'label' => $label,
        ];
    }
}
