<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class AuthenticatedPageSmokeTest extends HttpIntegrationTestCase
{
    public function testLoginPageLoadsForGuests(): void
    {
        $response = $this->dispatchJson('GET', '/login', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Sign in', $response['content']);
        self::assertStringContainsString('id="login-form"', $response['content']);
    }

    public function testDashboardPageLoadsForSuperAdmin(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);

        $response = $this->dispatchJson('GET', '/dashboard', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Dashboard', $response['content']);
        self::assertStringContainsString('data-dashboard', $response['content']);
    }

    public function testAnimalEditPageLoadsForAuthorizedUser(): void
    {
        $user = $this->createUser('super_admin');
        $animal = $this->createAnimal();
        $this->authenticateUser($user);

        $response = $this->dispatchJson('GET', '/animals/' . $animal['id'] . '/edit', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Edit Animal', $response['content']);
        self::assertStringContainsString('id="animal-form"', $response['content']);
        self::assertStringContainsString('data-mode="edit"', $response['content']);
    }

    public function testAnimalIndexPageLoadsForAuthorizedUser(): void
    {
        $user = $this->createUser('super_admin');
        $this->createAnimal();
        $this->authenticateUser($user);

        $response = $this->dispatchJson('GET', '/animals', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('<h1>Animals</h1>', $response['content']);
        self::assertStringContainsString('id="animal-list-page"', $response['content']);
    }
}
