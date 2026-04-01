<?php

declare(strict_types=1);

namespace Tests\Views;

use App\Core\View;
use PHPUnit\Framework\TestCase;

abstract class ViewSmokeTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $_SESSION = [];
        $_SERVER['REQUEST_URI'] = '/dashboard';
        $GLOBALS['app'] = [
            'name' => 'Catarman Animal Shelter',
            'settings' => [
                'app_name' => 'Catarman Animal Shelter',
                'organization_name' => 'Catarman Dog Pound',
            ],
        ];
    }

    protected function defaultUser(): array
    {
        return [
            'id' => 1,
            'first_name' => 'Kenneth',
            'last_name' => 'Laron',
            'role_name' => 'super_admin',
            'role_display_name' => 'Super Admin',
            'permissions' => [
                'animals.read',
                'kennels.read',
                'medical.read',
                'adoptions.read',
                'billing.read',
                'inventory.read',
                'reports.read',
                'users.read',
            ],
        ];
    }

    protected function renderApp(string $view, array $data = [], string $uri = '/dashboard'): string
    {
        $_SERVER['REQUEST_URI'] = $uri;

        return View::render($view, array_merge([
            'title' => 'Smoke Test',
            'csrfToken' => 'test-token',
            'user' => $this->defaultUser(),
            'currentUser' => $this->defaultUser(),
            'extraCss' => [],
            'extraJs' => [],
        ], $data), 'layouts.app');
    }

    protected function renderPublic(string $view, array $data = [], string $uri = '/adopt'): string
    {
        $_SERVER['REQUEST_URI'] = $uri;

        return View::render($view, array_merge([
            'title' => 'Adopt',
            'currentUser' => null,
            'extraCss' => ['/assets/css/portal.css'],
            'extraJs' => ['/assets/js/portal.js'],
        ], $data), 'layouts.public');
    }

    protected function featuredAnimals(): array
    {
        return [[
            'id' => 12,
            'animal_id' => 'AN-2026-0012',
            'name' => 'Luna',
            'breed_name' => 'Aspin',
            'species' => 'Dog',
            'gender' => 'Female',
            'size' => 'Medium',
            'primary_photo_path' => null,
        ]];
    }
}
