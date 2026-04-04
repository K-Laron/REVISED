# Reliability Hardening Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add repeatable release safety checks for routes, critical page journeys, and existing frontend smoke coverage without changing application behavior.

**Architecture:** Reuse the current PHPUnit and HTTP integration harness for the first pass, add one PowerShell release-check command under `scripts/`, and wire the same command into a minimal GitHub Actions workflow. Keep the route surface locked through tests instead of introducing a second route registry.

**Tech Stack:** PHP 8.2, PHPUnit 10, PowerShell, Node.js, GitHub Actions

---

### Task 1: Lock The Published Route Surface

**Files:**
- Create: `tests/Routes/WebRouteRegistrationTest.php`
- Create: `tests/Routes/RouteDocumentationSyncTest.php`
- Modify: `tests/Routes/ApiRouteRegistrationTest.php`

- [ ] **Step 1: Write the failing web-route and docs-sync tests**

```php
<?php

declare(strict_types=1);

namespace Tests\Routes;

use PHPUnit\Framework\TestCase;

final class WebRouteRegistrationTest extends TestCase
{
    public function testRepresentativeWebRoutesRemainRegistered(): void
    {
        $routes = $this->registeredRoutes();

        self::assertArrayHasKey('/', $routes['GET']);
        self::assertArrayHasKey('/login', $routes['GET']);
        self::assertArrayHasKey('/dashboard', $routes['GET']);
        self::assertArrayHasKey('/animals/{id}/edit', $routes['GET']);
        self::assertArrayHasKey('/adopt/apply', $routes['GET']);
        self::assertCount(33, $routes['GET']);
        self::assertCount(1, $routes['POST']);
    }

    private function registeredRoutes(): array
    {
        $router = new class {
            public array $routes = ['GET' => [], 'POST' => []];

            public function get(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['GET'][$path] = compact('handler', 'middleware');
            }

            public function post(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['POST'][$path] = compact('handler', 'middleware');
            }
        };

        require dirname(__DIR__, 2) . '/routes/web.php';

        return $router->routes;
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Tests\Routes;

use PHPUnit\Framework\TestCase;

final class RouteDocumentationSyncTest extends TestCase
{
    public function testLivingDocsMentionCurrentPublishedRouteCounts(): void
    {
        $readme = file_get_contents(dirname(__DIR__, 2) . '/README.md');
        $architecture = file_get_contents(dirname(__DIR__, 2) . '/ARCHITECTURE.md');
        $apiRoutes = file_get_contents(dirname(__DIR__, 2) . '/API_ROUTES.md');

        self::assertStringContainsString('`34` web routes', (string) $readme);
        self::assertStringContainsString('`127` production API routes', (string) $readme);
        self::assertStringContainsString('/api/dashboard/bootstrap', (string) $apiRoutes);
        self::assertStringContainsString('/api/animals/{id}/photos/reorder', (string) $apiRoutes);
        self::assertStringContainsString('/api/search/global', (string) $apiRoutes);
        self::assertStringContainsString('Current base-table count: `39`', (string) $architecture);
    }
}
```

- [ ] **Step 2: Run the route tests to verify they fail before implementation**

Run:

```powershell
php vendor/bin/phpunit tests/Routes/WebRouteRegistrationTest.php tests/Routes/RouteDocumentationSyncTest.php -v
```

Expected:

```text
FAILURES!
Tests: 2, Assertions: 0, Failures: 2
```

- [ ] **Step 3: Implement the route locking suite**

```php
<?php

declare(strict_types=1);

namespace Tests\Routes;

use PHPUnit\Framework\TestCase;

final class WebRouteRegistrationTest extends TestCase
{
    public function testRepresentativeWebRoutesRemainRegistered(): void
    {
        $routes = $this->registeredRoutes();

        self::assertArrayHasKey('/', $routes['GET']);
        self::assertArrayHasKey('/login', $routes['GET']);
        self::assertArrayHasKey('/forgot-password', $routes['GET']);
        self::assertArrayHasKey('/dashboard', $routes['GET']);
        self::assertArrayHasKey('/animals', $routes['GET']);
        self::assertArrayHasKey('/animals/{id}/edit', $routes['GET']);
        self::assertArrayHasKey('/reports/viewer', $routes['GET']);
        self::assertArrayHasKey('/adopt/apply', $routes['GET']);
        self::assertCount(33, $routes['GET']);
        self::assertCount(1, $routes['POST']);
    }

    private function registeredRoutes(): array
    {
        $router = new class {
            public array $routes = ['GET' => [], 'POST' => []];

            public function get(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['GET'][$path] = compact('handler', 'middleware');
            }

            public function post(string $path, callable|string $handler, array $middleware = []): void
            {
                $this->routes['POST'][$path] = compact('handler', 'middleware');
            }
        };

        require dirname(__DIR__, 2) . '/routes/web.php';

        return $router->routes;
    }
}
```

```php
public function testApiRoutesAreSplitIntoModuleFilesAndStillRegisterRepresentativeEndpoints(): void
{
    $routeDirectory = dirname(__DIR__, 2) . '/routes/api';
    $routes = $this->registeredRoutes(true);

    self::assertCount(15, glob($routeDirectory . '/*.php'));
    self::assertCount(61, $routes['GET']);
    self::assertCount(39, $routes['POST']);
    self::assertCount(21, $routes['PUT']);
    self::assertCount(0, $routes['PATCH']);
    self::assertCount(7, $routes['DELETE']);

    self::assertArrayHasKey('/api/dashboard/bootstrap', $routes['GET']);
    self::assertArrayHasKey('/api/animals/{id}/photos/reorder', $routes['PUT']);
    self::assertArrayHasKey('/api/search/global', $routes['GET']);
}
```

- [ ] **Step 4: Run the route suite and verify it passes**

Run:

```powershell
php vendor/bin/phpunit tests/Routes -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add tests/Routes/WebRouteRegistrationTest.php tests/Routes/RouteDocumentationSyncTest.php tests/Routes/ApiRouteRegistrationTest.php
git commit -m "test: lock published route surface"
```

### Task 2: Add HTTP Journey Smoke Coverage

**Files:**
- Create: `tests/Integration/Http/AuthenticatedPageSmokeTest.php`
- Create: `tests/Integration/Http/PublicAdoptionJourneyHttpTest.php`
- Modify: `tests/Integration/Http/ApiDashboardHttpTest.php`

- [ ] **Step 1: Write the failing HTTP smoke tests**

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class AuthenticatedPageSmokeTest extends HttpIntegrationTestCase
{
    public function testDashboardPageLoadsForSuperAdmin(): void
    {
        $user = $this->createUser('super_admin');
        $this->authenticateUser($user);

        $response = $this->dispatchJson('GET', '/dashboard', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Dashboard', $response['content']);
        self::assertStringContainsString('data-dashboard', $response['content']);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class PublicAdoptionJourneyHttpTest extends HttpIntegrationTestCase
{
    public function testApplyPageLoadsForAuthenticatedAdopter(): void
    {
        $adopter = $this->createUser('adopter');
        $this->authenticateUser($adopter);

        $response = $this->dispatchJson('GET', '/adopt/apply', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Apply For Adoption', $response['content']);
        self::assertStringContainsString('availableAnimals', $response['content']);
    }
}
```

- [ ] **Step 2: Run the new smoke tests and confirm they fail**

Run:

```powershell
php vendor/bin/phpunit tests/Integration/Http/AuthenticatedPageSmokeTest.php tests/Integration/Http/PublicAdoptionJourneyHttpTest.php -v
```

Expected:

```text
FAILURES!
Tests: 2, Assertions: 0, Failures: 2
```

- [ ] **Step 3: Implement the page smoke suite**

```php
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
        self::assertStringContainsString('Login', $response['content']);
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
        self::assertStringContainsString('animal-photo-upload-form', $response['content']);
    }
}
```

```php
<?php

declare(strict_types=1);

namespace Tests\Integration\Http;

require_once __DIR__ . '/HttpIntegrationTestCase.php';

final class PublicAdoptionJourneyHttpTest extends HttpIntegrationTestCase
{
    public function testAnimalsListingLoadsForGuests(): void
    {
        $response = $this->dispatchJson('GET', '/adopt/animals', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Available Animals', $response['content']);
    }

    public function testApplyPageLoadsForAuthenticatedAdopter(): void
    {
        $adopter = $this->createUser('adopter');
        $this->authenticateUser($adopter);

        $response = $this->dispatchJson('GET', '/adopt/apply', [], [], ['HTTP_ACCEPT' => 'text/html']);

        self::assertSame(200, $response['status']);
        self::assertStringContainsString('Apply For Adoption', $response['content']);
        self::assertStringContainsString('portal-application-form', $response['content']);
    }
}
```

```php
public function testDashboardBootstrapReturnsAllPrimaryWidgets(): void
{
    $user = $this->createUser('super_admin');
    $this->authenticateUser($user);

    $response = $this->dispatchJson('GET', '/api/dashboard/bootstrap');

    self::assertSame(200, $response['status']);
    self::assertArrayHasKey('stats', $response['json']['data']);
    self::assertArrayHasKey('intake', $response['json']['data']['charts']);
    self::assertArrayHasKey('adoptions', $response['json']['data']['charts']);
    self::assertArrayHasKey('occupancy', $response['json']['data']['charts']);
    self::assertArrayHasKey('medical', $response['json']['data']['charts']);
    self::assertArrayHasKey('activity', $response['json']['data']);
}
```

- [ ] **Step 4: Run the smoke suite and verify it passes**

Run:

```powershell
php vendor/bin/phpunit tests/Integration/Http/AuthenticatedPageSmokeTest.php tests/Integration/Http/PublicAdoptionJourneyHttpTest.php tests/Integration/Http/ApiDashboardHttpTest.php -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add tests/Integration/Http/AuthenticatedPageSmokeTest.php tests/Integration/Http/PublicAdoptionJourneyHttpTest.php tests/Integration/Http/ApiDashboardHttpTest.php
git commit -m "test: add http smoke coverage for critical journeys"
```

### Task 3: Add One Local Release Check Command

**Files:**
- Create: `scripts/run-release-checks.ps1`

- [ ] **Step 1: Write the failing release-check script contract**

```powershell
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

Write-Host 'Running route tests...'
php vendor/bin/phpunit tests/Routes -v

Write-Host 'Running critical HTTP smoke tests...'
php vendor/bin/phpunit tests/Integration/Http/AuthenticatedPageSmokeTest.php tests/Integration/Http/PublicAdoptionJourneyHttpTest.php tests/Integration/Http/ApiDashboardHttpTest.php -v

Write-Host 'Running full PHPUnit suite...'
php vendor/bin/phpunit

Write-Host 'Running frontend smoke tests...'
node tests/Frontend/animals-inline-photo-upload.test.js
```

- [ ] **Step 2: Run the script before it exists and confirm failure**

Run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/run-release-checks.ps1
```

Expected:

```text
The argument 'scripts/run-release-checks.ps1' is not recognized
```

- [ ] **Step 3: Implement the release-check script**

```powershell
Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

function Invoke-Step {
    param(
        [string] $Label,
        [scriptblock] $Command
    )

    Write-Host "==> $Label"
    & $Command
    Write-Host "<== $Label complete"
}

Invoke-Step 'Route tests' {
    php vendor/bin/phpunit tests/Routes -v
}

Invoke-Step 'Critical HTTP smoke tests' {
    php vendor/bin/phpunit tests/Integration/Http/AuthenticatedPageSmokeTest.php tests/Integration/Http/PublicAdoptionJourneyHttpTest.php tests/Integration/Http/ApiDashboardHttpTest.php -v
}

Invoke-Step 'Full PHPUnit suite' {
    php vendor/bin/phpunit
}

Invoke-Step 'Frontend smoke tests' {
    node tests/Frontend/animals-inline-photo-upload.test.js
}
```

- [ ] **Step 4: Run the release-check script and verify success**

Run:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/run-release-checks.ps1
```

Expected:

```text
==> Route tests
OK
==> Critical HTTP smoke tests
OK
==> Full PHPUnit suite
OK
==> Frontend smoke tests
<== Frontend smoke tests complete
```

- [ ] **Step 5: Commit**

```powershell
git add scripts/run-release-checks.ps1
git commit -m "chore: add release verification command"
```

### Task 4: Wire The Release Gate Into CI And Docs

**Files:**
- Create: `.github/workflows/ci.yml`
- Modify: `README.md`

- [ ] **Step 1: Write the failing CI workflow and docs note**

```yaml
name: CI

on:
  push:
    branches: ["**"]
  pull_request:

jobs:
  checks:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
```

```md
## Verification

Run the release gate locally:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/run-release-checks.ps1
```
```

- [ ] **Step 2: Run the local release script and confirm the docs section is still missing**

Run:

```powershell
Select-String -Path README.md -Pattern 'run-release-checks'
```

Expected:

```text
0 matches
```

- [ ] **Step 3: Implement the CI workflow and README instructions**

```yaml
name: CI

on:
  push:
    branches:
      - main
      - 'codex/**'
  pull_request:

jobs:
  checks:
    runs-on: ubuntu-latest

    steps:
      - name: Check out repository
        uses: actions/checkout@v4

      - name: Set up PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: none

      - name: Set up Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'

      - name: Install Composer dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Install Node dependencies
        run: npm install

      - name: Run release checks
        shell: pwsh
        run: ./scripts/run-release-checks.ps1
```

```md
## Verification

Run the release gate locally before pushing:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/run-release-checks.ps1
```

This command runs route coverage, critical HTTP smoke tests, the full PHPUnit suite, and the tracked frontend smoke test.
```

- [ ] **Step 4: Verify the release gate and docs reference**

Run:

```powershell
Select-String -Path README.md -Pattern 'run-release-checks'
powershell -ExecutionPolicy Bypass -File scripts/run-release-checks.ps1
```

Expected:

```text
README.md: Verification
OK
```

- [ ] **Step 5: Commit**

```powershell
git add .github/workflows/ci.yml README.md
git commit -m "ci: enforce release verification checks"
```
