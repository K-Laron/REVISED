<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

class View
{
    public static function render(string $template, array $data = [], ?string $layout = null): string
    {
        $viewPath = dirname(__DIR__, 2) . '/views/' . str_replace('.', '/', $template) . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException(sprintf('View [%s] not found.', $template));
        }

        $currentUser = $data['currentUser'] ?? ($_SESSION['auth.user'] ?? null);
        $can = static function (?string $permission = null, array|string $roles = []) use ($currentUser): bool {
            if (!is_array($currentUser)) {
                return false;
            }

            $roleName = (string) ($currentUser['role_name'] ?? '');
            $allowedRoles = is_array($roles) ? $roles : [$roles];
            $allowedRoles = array_values(array_filter(array_map('strval', $allowedRoles)));

            if ($roleName === 'super_admin') {
                return true;
            }

            if ($allowedRoles !== [] && in_array($roleName, $allowedRoles, true)) {
                return true;
            }

            if ($permission === null || $permission === '') {
                return false;
            }

            return in_array($permission, $currentUser['permissions'] ?? [], true);
        };

        $data['currentUser'] = $currentUser;
        $data['can'] = $can;

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        if ($layout === null) {
            return $content ?: '';
        }

        $layoutPath = dirname(__DIR__, 2) . '/views/' . str_replace('.', '/', $layout) . '.php';
        if (!is_file($layoutPath)) {
            throw new RuntimeException(sprintf('Layout [%s] not found.', $layout));
        }

        ob_start();
        require $layoutPath;

        return ob_get_clean() ?: '';
    }
}
