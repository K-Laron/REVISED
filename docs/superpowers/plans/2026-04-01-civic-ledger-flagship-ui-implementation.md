# Civic Ledger Flagship UI Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement the approved Civic Ledger flagship redesign across the shared internal shell, dashboard, global search, settings, and public adopter landing page without changing routes, controllers, or API contracts.

**Architecture:** Keep the PHP MVC structure intact and treat the redesign as a view-layer rollout. Shared layouts, partials, and design tokens establish the Civic Ledger visual system first; each flagship page then adopts that system through scoped template, CSS, and JS updates while preserving the existing IDs, data attributes, form names, and API endpoints that current logic depends on.

**Tech Stack:** PHP 8.2 MVC views, vanilla CSS, vanilla JavaScript, local Chart.js, PHPUnit, optional local PHP dev server (`php -S localhost:8000 -t public`)

---

## File Structure Map

### Shared UI Foundation

- Modify: `views/layouts/app.php`
- Modify: `views/layouts/public.php`
- Modify: `views/partials/sidebar.php`
- Modify: `views/partials/header.php`
- Modify: `public/assets/css/variables.css`
- Modify: `public/assets/css/base.css`
- Modify: `public/assets/css/components.css`
- Modify: `public/assets/css/layout.css`
- Modify: `public/assets/css/responsive.css`
- Create: `tests/Views/ViewSmokeTestCase.php`
- Create: `tests/Views/AppShellViewTest.php`

### Dashboard

- Modify: `views/dashboard/index.php`
- Modify: `public/assets/css/dashboard.css`
- Modify: `public/assets/js/dashboard.js`
- Create: `tests/Views/DashboardViewTest.php`
- Reuse: `tests/Controllers/DashboardControllerTest.php`

### Global Search

- Modify: `views/search/index.php`
- Modify: `public/assets/css/search.css`
- Modify: `public/assets/js/search.js`
- Create: `tests/Views/SearchViewTest.php`
- Reuse: `tests/Integration/Http/ApiSearchHttpTest.php`

### Settings

- Modify: `views/settings/index.php`
- Modify: `public/assets/css/settings.css`
- Modify: `public/assets/js/settings.js`
- Create: `tests/Views/SettingsViewTest.php`
- Reuse: `tests/Integration/Http/ApiSystemHttpTest.php`

### Public Portal Landing

- Modify: `views/portal/landing.php`
- Modify: `public/assets/css/portal.css`
- Modify: `public/assets/js/portal.js`
- Create: `tests/Views/PortalLandingViewTest.php`

### Documentation and Final Verification

- Modify: `README.md`
- Modify: `PAGE_LAYOUTS.md`
- Reuse: all new `tests/Views/*.php`
- Reuse: `tests/Controllers/DashboardControllerTest.php`
- Reuse: `tests/Integration/Http/ApiSearchHttpTest.php`
- Reuse: `tests/Integration/Http/ApiSystemHttpTest.php`

## Guardrails

- Do not change any route path, controller signature, API payload, form field name, or existing query parameter contract.
- Do not replace the current vanilla JS approach with a framework.
- Do not add npm or Composer dependencies.
- Keep Chart.js local and untouched.
- Preserve the existing dark-mode toggle, skip links, CSRF token flow, and data attributes already used by page scripts.
- Use `JetBrains Mono` only for metadata, badges, identifiers, timestamps, and operational labels.
- Keep the current public CTA logic and featured-animal carousel data attributes intact.

### Task 1: Establish Civic Ledger Tokens, Typography, and Layout Test Helpers

**Files:**
- Create: `tests/Views/ViewSmokeTestCase.php`
- Create: `tests/Views/AppShellViewTest.php`
- Modify: `views/layouts/app.php`
- Modify: `views/layouts/public.php`
- Modify: `public/assets/css/variables.css`
- Modify: `public/assets/css/base.css`

- [ ] **Step 1: Write the failing layout smoke tests**

```php
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
```

```php
<?php

declare(strict_types=1);

namespace Tests\Views;

final class AppShellViewTest extends ViewSmokeTestCase
{
    public function testAppLayoutLoadsCivicLedgerFontsAndThemeMarker(): void
    {
        $html = $this->renderApp('dashboard.index');

        self::assertStringContainsString('Lexend', $html);
        self::assertStringContainsString('Source+Sans+3', $html);
        self::assertStringContainsString('JetBrains+Mono', $html);
        self::assertStringContainsString('data-ui-theme="civic-ledger"', $html);
    }

    public function testPublicLayoutKeepsSkipLinkAndUsesTheSameFontStack(): void
    {
        $html = $this->renderPublic('portal.landing', [
            'featuredAnimals' => $this->featuredAnimals(),
        ]);

        self::assertStringContainsString('href="#public-main"', $html);
        self::assertStringContainsString('data-ui-theme="civic-ledger"', $html);
        self::assertStringContainsString('JetBrains+Mono', $html);
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Views/AppShellViewTest.php`

Expected: FAIL because the layouts still load `Fira Sans` / `Fira Code` and do not output the `data-ui-theme="civic-ledger"` marker.

- [ ] **Step 3: Write the minimal shared-token implementation**

```php
<!-- views/layouts/app.php -->
<html lang="en" data-ui-theme="civic-ledger">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Lexend:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
```

```php
<!-- views/layouts/public.php -->
<html lang="en" data-ui-theme="civic-ledger">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&family=Lexend:wght@400;500;600;700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet">
```

```css
/* public/assets/css/variables.css */
:root {
  --color-bg-primary: #f8fafc;
  --color-bg-secondary: #eef2f7;
  --color-bg-elevated: #ffffff;
  --color-bg-warm: #fff7ed;
  --color-bg-accent: #0f172a;
  --color-text-primary: #020617;
  --color-text-secondary: #475569;
  --color-text-tertiary: #64748b;
  --color-border-default: #cbd5e1;
  --color-border-strong: #94a3b8;
  --color-border-focus: #1d4ed8;
  --color-accent-primary: #1e3a8a;
  --color-accent-primary-hover: #1d4ed8;
  --color-accent-info: #0369a1;
  --color-accent-warning: #b45309;
  --color-accent-success: #15803d;
  --font-family-primary: 'Source Sans 3', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-family-heading: 'Lexend', 'Source Sans 3', sans-serif;
  --font-family-mono: 'JetBrains Mono', 'SFMono-Regular', Consolas, monospace;
  --radius-md: 16px;
  --radius-lg: 24px;
  --radius-xl: 32px;
  --shadow-sm: 0 10px 24px rgba(15, 23, 42, 0.06);
  --shadow-md: 0 22px 44px rgba(15, 23, 42, 0.09);
  --shadow-lg: 0 32px 70px rgba(15, 23, 42, 0.12);
  --focus-glow: 0 0 0 4px rgba(29, 78, 216, 0.18);
}
```

```css
/* public/assets/css/base.css */
body {
  font-family: var(--font-family-primary);
}

h1,
h2,
h3,
h4,
.page-title h1,
.sidebar-brand strong,
.public-brand-copy strong {
  font-family: var(--font-family-heading);
  letter-spacing: -0.025em;
}

.mono,
code,
.field-label,
.badge,
.breadcrumb {
  font-family: var(--font-family-mono);
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Views/AppShellViewTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Views/ViewSmokeTestCase.php tests/Views/AppShellViewTest.php views/layouts/app.php views/layouts/public.php public/assets/css/variables.css public/assets/css/base.css
git commit -m "style: establish Civic Ledger typography and tokens"
```

### Task 2: Rebuild the Shared Internal Shell Chrome

**Files:**
- Modify: `views/partials/sidebar.php`
- Modify: `views/partials/header.php`
- Modify: `public/assets/css/components.css`
- Modify: `public/assets/css/layout.css`
- Modify: `public/assets/css/responsive.css`
- Modify: `tests/Views/AppShellViewTest.php`

- [ ] **Step 1: Extend the app-shell smoke test with the new shell markers**

```php
public function testAppLayoutRendersTheNewCommandRailAndHeaderShell(): void
{
    $html = $this->renderApp('dashboard.index');

    self::assertStringContainsString('sidebar-rail-summary', $html);
    self::assertStringContainsString('sidebar-group-card', $html);
    self::assertStringContainsString('topbar-command-shell', $html);
    self::assertStringContainsString('topbar-status-pill', $html);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Views/AppShellViewTest.php`

Expected: FAIL because the current sidebar and header markup do not expose the new Civic Ledger shell classes.

- [ ] **Step 3: Write the minimal shell markup and CSS**

```php
<!-- views/partials/sidebar.php -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo"><?= htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') ?></div>
        <div class="sidebar-brand-copy">
            <span class="sidebar-brand-kicker">Operations Ledger</span>
            <strong><?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></strong>
            <div class="text-muted"><?= htmlspecialchars((string) ($appSettings['organization_name'] ?? 'Animal Shelter'), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <div class="sidebar-rail-summary">
        <span class="sidebar-rail-label">Command rail</span>
        <strong>Navigate every shelter workflow from one place.</strong>
    </div>
    <nav class="sidebar-nav">
        <?php foreach ($groups as $groupLabel => $links): ?>
            <section class="sidebar-group-card">
                <span class="sidebar-group-label"><?= htmlspecialchars($groupLabel, ENT_QUOTES, 'UTF-8') ?></span>
                <?php foreach ($links as $link): ?>
                    <?php if (isset($link['roles']) && !($can ?? static fn (): bool => true)(null, $link['roles'])) continue; ?>
                    <?php if (isset($link['permission']) && !($can ?? static fn (): bool => true)($link['permission'])) continue; ?>
                    <?php $isActive = $currentPath === $link['href'] || str_starts_with($currentPath, $link['href'] . '/'); ?>
                    <a class="sidebar-link<?= $isActive ? ' is-active' : '' ?>" href="<?= htmlspecialchars($link['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= $link['icon'] ?>
                        <span class="sidebar-link-label"><?= htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </section>
        <?php endforeach; ?>
    </nav>
</aside>
```

```php
<!-- views/partials/header.php -->
<header class="topbar">
    <div class="topbar-context">
        <button class="icon-button mobile-menu-toggle" type="button" data-sidebar-toggle aria-label="Open navigation">
            <?= $menuIcon ?>
        </button>
        <div class="topbar-heading">
            <span class="topbar-eyebrow">Shelter Operations</span>
            <strong>Command surface</strong>
        </div>
    </div>
    <form class="topbar-search topbar-command-shell" action="/search" method="get">
        <label class="sr-only" for="global-search-input">Global search</label>
        <?= $searchIcon ?>
        <input id="global-search-input" type="search" name="q" value="<?= htmlspecialchars($headerSearchValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search an animal ID, adopter, invoice, or SKU" minlength="2" required data-global-search-input>
        <span class="topbar-status-pill">Ctrl /</span>
    </form>
    <div class="topbar-actions">
        <span class="topbar-status-pill"><?= htmlspecialchars($authUser['role_display_name'] ?? 'Guest', ENT_QUOTES, 'UTF-8') ?></span>
        <div class="user-chip">
            <div class="user-avatar"><?= htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8') ?></div>
            <div class="user-chip-meta">
                <div class="user-chip-name"><?= htmlspecialchars(($authUser['first_name'] ?? 'Guest') . ' ' . ($authUser['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <small class="mono"><?= htmlspecialchars($authUser['role_name'] ?? 'guest', ENT_QUOTES, 'UTF-8') ?></small>
            </div>
        </div>
    </div>
</header>
```

```css
/* public/assets/css/layout.css */
.sidebar-rail-summary {
  display: grid;
  gap: var(--space-2);
  padding: var(--space-4);
  border: 1px solid color-mix(in srgb, var(--color-accent-primary) 22%, var(--color-border-default));
  border-radius: var(--radius-lg);
  background: linear-gradient(155deg, rgba(15, 23, 42, 0.96), rgba(30, 58, 138, 0.92));
  color: #f8fafc;
}

.sidebar-group-card {
  display: grid;
  gap: var(--space-2);
  padding: var(--space-3);
  border: 1px solid var(--color-border-light);
  border-radius: var(--radius-lg);
  background: color-mix(in srgb, var(--color-bg-elevated) 94%, var(--color-bg-secondary));
}

.topbar-context {
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.topbar-command-shell {
  max-width: 680px;
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-bg-elevated) 96%, white), color-mix(in srgb, var(--color-bg-secondary) 92%, white));
}

.topbar-status-pill {
  display: inline-flex;
  align-items: center;
  min-height: 36px;
  padding: 0 var(--space-3);
  border-radius: var(--radius-full);
  border: 1px solid var(--color-border-default);
  background: color-mix(in srgb, var(--color-bg-secondary) 85%, var(--color-bg-elevated));
  font-family: var(--font-family-mono);
  font-size: var(--font-size-xs);
}
```

```css
/* public/assets/css/responsive.css */
@media (max-width: 1024px) {
  .topbar {
    grid-template-columns: 1fr;
    align-items: stretch;
  }

  .topbar-command-shell {
    max-width: none;
  }

  .sidebar-rail-summary {
    display: none;
  }
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Views/AppShellViewTest.php tests/Controllers/DashboardControllerTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Views/AppShellViewTest.php views/partials/sidebar.php views/partials/header.php public/assets/css/components.css public/assets/css/layout.css public/assets/css/responsive.css
git commit -m "style: redesign internal app shell"
```

### Task 3: Redesign the Dashboard as an Operational Briefing Surface

**Files:**
- Create: `tests/Views/DashboardViewTest.php`
- Modify: `views/dashboard/index.php`
- Modify: `public/assets/css/dashboard.css`
- Modify: `public/assets/js/dashboard.js`
- Reuse: `tests/Controllers/DashboardControllerTest.php`

- [ ] **Step 1: Write the failing dashboard view smoke test**

```php
<?php

declare(strict_types=1);

namespace Tests\Views;

final class DashboardViewTest extends ViewSmokeTestCase
{
    public function testDashboardRendersTheBriefingLayoutMarkers(): void
    {
        $html = $this->renderApp('dashboard.index', [
            'title' => 'Dashboard',
        ]);

        self::assertStringContainsString('dashboard-briefing', $html);
        self::assertStringContainsString('dashboard-kpi-grid', $html);
        self::assertStringContainsString('dashboard-action-deck', $html);
        self::assertStringContainsString('dashboard-activity-feed', $html);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit tests/Views/DashboardViewTest.php`

Expected: FAIL because the current dashboard uses the older generic `stats-grid` / `dashboard-grid` structure only.

- [ ] **Step 3: Write the minimal dashboard markup, CSS, and JS**

```php
<!-- views/dashboard/index.php -->
<section class="dashboard-briefing" data-dashboard>
    <article class="card dashboard-briefing-hero">
        <div class="dashboard-briefing-copy">
            <span class="badge badge-info">Live Operations</span>
            <h1>Dashboard</h1>
            <p class="text-muted">Review live shelter intake, occupancy, adoption movement, and recent activity from one command surface.</p>
            <div class="breadcrumb">Home &gt; Dashboard</div>
        </div>
        <div class="dashboard-briefing-side">
            <div class="dashboard-briefing-meta">
                <span class="field-label">Current operator</span>
                <strong><?= htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="mono"><?= htmlspecialchars($user['role_display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <button class="btn-secondary" id="logout">Logout</button>
        </div>
    </article>
</section>

<section class="dashboard-kpi-grid" id="stats-grid"></section>

<section class="dashboard-command-grid">
    <article class="card dashboard-chart-panel">
        <div class="dashboard-section-heading">
            <h3>Intake trend</h3>
            <p class="text-muted">Animal intake over the last 12 months.</p>
        </div>
        <canvas id="intake-chart"></canvas>
    </article>
    <aside class="card dashboard-action-deck">
        <div>
            <h3>Quick actions</h3>
            <p class="text-muted">Jump directly into the workflows staff use most often.</p>
        </div>
        <div class="quick-actions">
            <?php
                $dashboardQuickActions = [
                    ['label' => 'New Intake', 'href' => '/animals/create', 'class' => 'btn-primary', 'permission' => 'animals.create'],
                    ['label' => 'View Animals', 'href' => '/animals', 'class' => 'btn-secondary', 'permission' => 'animals.read'],
                    ['label' => 'View Kennels', 'href' => '/kennels', 'class' => 'btn-secondary', 'permission' => 'kennels.read'],
                    ['label' => 'Generate Report', 'href' => '/reports', 'class' => 'btn-secondary', 'permission' => 'reports.read'],
                ];
                $visibleQuickActions = array_values(array_filter($dashboardQuickActions, static fn (array $action): bool => ($can ?? static fn (): bool => false)($action['permission'])));
            ?>
            <?php if ($visibleQuickActions !== []): ?>
                <?php foreach ($visibleQuickActions as $action): ?>
                    <button class="<?= htmlspecialchars($action['class'], ENT_QUOTES, 'UTF-8') ?>" type="button" data-quick-link="<?= htmlspecialchars($action['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars($action['label'], ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted">No quick actions are available for your current access level.</div>
            <?php endif; ?>
        </div>
    </aside>
</section>

<section class="dashboard-command-grid">
    <article class="card dashboard-chart-panel">
        <div class="dashboard-section-heading">
            <h3>Kennel occupancy</h3>
            <p class="text-muted">Available, occupied, maintenance, and quarantine capacity.</p>
        </div>
        <canvas id="occupancy-chart"></canvas>
    </article>
    <article class="card dashboard-activity-feed">
        <div class="dashboard-section-heading">
            <h3>Recent activity</h3>
            <p class="text-muted">Latest audit log entries across shelter modules.</p>
        </div>
        <div class="activity-list" id="activity-list"></div>
    </article>
</section>
```

```css
/* public/assets/css/dashboard.css */
.dashboard-briefing {
  display: grid;
  gap: var(--space-5);
}

.dashboard-briefing-hero {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(260px, 0.8fr);
  gap: var(--space-6);
  background: linear-gradient(155deg, rgba(15, 23, 42, 0.98), rgba(30, 58, 138, 0.9));
  color: #f8fafc;
}

.dashboard-kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: var(--space-4);
}

.dashboard-command-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.9fr);
  gap: var(--space-5);
}

.dashboard-action-deck,
.dashboard-activity-feed,
.dashboard-chart-panel {
  min-height: 100%;
}
```

```js
// public/assets/js/dashboard.js
function renderStats(items) {
  const root = document.getElementById('stats-grid');
  root.innerHTML = '';
  items.forEach((item) => {
    const card = document.createElement('article');
    card.className = 'card stat-card';
    card.innerHTML = `
      <div class="stat-label">${item.label}</div>
      <div class="stat-value mono">${item.value}</div>
      <div class="stat-meta">${item.meta}</div>
    `;
    root.appendChild(card);
  });
}

function renderActivity(items) {
  const root = document.getElementById('activity-list');
  root.innerHTML = '';
  if (!items.length) {
    root.innerHTML = '<div class="activity-item"><strong>No recent activity</strong><span class="text-muted">Audit log entries will appear here.</span></div>';
    return;
  }

  items.forEach((item) => {
    const row = document.createElement('div');
    row.className = 'activity-item';
    row.innerHTML = `
      <span class="field-label">${item.module}</span>
      <strong>${item.action}</strong>
      <span class="mono">${item.record_id ?? '-'} · ${item.created_at}</span>
    `;
    root.appendChild(row);
  });
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Views/DashboardViewTest.php tests/Controllers/DashboardControllerTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Views/DashboardViewTest.php views/dashboard/index.php public/assets/css/dashboard.css public/assets/js/dashboard.js tests/Controllers/DashboardControllerTest.php
git commit -m "style: redesign dashboard briefing surface"
```

### Task 4: Redesign Global Search as a Command Center

**Files:**
- Create: `tests/Views/SearchViewTest.php`
- Modify: `views/search/index.php`
- Modify: `public/assets/css/search.css`
- Modify: `public/assets/js/search.js`
- Reuse: `tests/Integration/Http/ApiSearchHttpTest.php`

- [ ] **Step 1: Write the failing search view smoke test**

```php
<?php

declare(strict_types=1);

namespace Tests\Views;

final class SearchViewTest extends ViewSmokeTestCase
{
    public function testSearchPageRendersTheCommandCenterMarkers(): void
    {
        $html = $this->renderApp('search.index', [
            'title' => 'Global Search',
            'searchQuery' => '',
            'searchFilters' => ['modules' => [], 'per_section' => 5],
            'availableSearchModules' => [
                ['key' => 'animals', 'label' => 'Animals'],
                ['key' => 'billing', 'label' => 'Billing'],
            ],
            'availableSearchSecondaryFilters' => [],
            'extraCss' => ['/assets/css/search.css'],
            'extraJs' => ['/assets/js/search.js'],
        ], '/search');

        self::assertStringContainsString('search-command-shell', $html);
        self::assertStringContainsString('search-filter-dock', $html);
        self::assertStringContainsString('search-results-ledger', $html);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit tests/Views/SearchViewTest.php`

Expected: FAIL because the current search page does not render the new command-shell classes.

- [ ] **Step 3: Write the minimal search markup, CSS, and JS**

```php
<!-- views/search/index.php -->
<section class="search-command-shell page-title" id="search-page">
    <div class="page-title-meta">
        <span class="badge badge-info">Cross-module lookup</span>
        <h1>Global Search</h1>
        <div class="breadcrumb">Home &gt; Search</div>
        <p class="text-muted">Search across animals, adopters, adoptions, billing, inventory, medical, and staff records you can access.</p>
    </div>
</section>

<section class="card search-filter-dock">
    <form class="search-form" id="search-form">
        <div class="search-query-band">
            <label class="field search-filter-span-2">
                <span class="field-label">Find Records</span>
                <div class="search-input-row">
                    <input class="input search-command-input" type="search" name="q" value="<?= htmlspecialchars((string) ($searchQuery ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Try an animal ID, adopter name, invoice number, or SKU" minlength="2" required>
                    <button class="btn-primary" type="submit">Search</button>
                </div>
            </label>
            <div class="badge badge-info" id="search-total-badge">Ready</div>
        </div>
        <div class="search-filter-layout">
            <label class="field">
                <span class="field-label">Results Per Module</span>
                <select class="input" name="per_section">
                    <?php $selectedPerSection = (int) ($searchFilters['per_section'] ?? 5); ?>
                    <?php foreach ([3, 5, 10] as $size): ?>
                        <option value="<?= $size ?>" <?= $selectedPerSection === $size ? 'selected' : '' ?>><?= $size ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span class="field-label">Date From</span>
                <input class="input" type="date" name="date_from" value="<?= htmlspecialchars((string) ($searchFilters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <label class="field">
                <span class="field-label">Date To</span>
                <input class="input" type="date" name="date_to" value="<?= htmlspecialchars((string) ($searchFilters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </label>
        </div>
        <fieldset class="search-module-filters">
            <legend class="field-label">Modules</legend>
            <div class="search-module-list">
                <?php $selectedModules = array_values(array_filter((array) ($searchFilters['modules'] ?? []))); ?>
                <?php foreach (($availableSearchModules ?? []) as $module): ?>
                    <?php
                        $moduleKey = (string) ($module['key'] ?? '');
                        $isSelected = $selectedModules === [] || in_array($moduleKey, $selectedModules, true);
                    ?>
                    <label class="search-module-chip">
                        <input type="checkbox" name="modules[]" value="<?= htmlspecialchars($moduleKey, ENT_QUOTES, 'UTF-8') ?>" <?= $isSelected ? 'checked' : '' ?>>
                        <span><?= htmlspecialchars((string) ($module['label'] ?? $moduleKey), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </fieldset>
        <section class="search-secondary-shell">
            <div>
                <h4>Module-Specific Filters</h4>
                <p class="text-muted">Only filters for selected modules are applied.</p>
            </div>
            <div class="search-secondary-grid">
                <?php foreach (($availableSearchSecondaryFilters ?? []) as $filter): ?>
                    <?php
                        $filterKey = (string) ($filter['key'] ?? '');
                        $filterModule = (string) ($filter['module'] ?? '');
                        $selectedValue = (string) ($searchFilters[$filterKey] ?? '');
                    ?>
                    <label class="field search-secondary-field" data-module-filter="<?= htmlspecialchars($filterModule, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="field-label"><?= htmlspecialchars((string) ($filter['label'] ?? $filterKey), ENT_QUOTES, 'UTF-8') ?></span>
                        <select class="input" name="<?= htmlspecialchars($filterKey, ENT_QUOTES, 'UTF-8') ?>">
                            <option value="">All</option>
                            <?php foreach (($filter['options'] ?? []) as $option): ?>
                                <option value="<?= htmlspecialchars((string) ($option['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" <?= $selectedValue === (string) ($option['value'] ?? '') ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($option['label'] ?? $option['value'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>
    </form>
</section>

<section class="search-results-ledger" id="search-results"></section>
```

```css
/* public/assets/css/search.css */
.search-command-shell {
  display: grid;
  gap: var(--space-4);
}

.search-filter-dock {
  display: grid;
  gap: var(--space-5);
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-bg-elevated) 96%, white), color-mix(in srgb, var(--color-bg-secondary) 88%, white));
}

.search-query-band {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: var(--space-4);
}

.search-command-input {
  min-height: 58px;
  font-size: 1.05rem;
}

.search-results-ledger {
  display: grid;
  gap: var(--space-6);
}

.search-section-ledger .badge,
.search-result-code {
  font-family: var(--font-family-mono);
}
```

```js
// public/assets/js/search.js
results.innerHTML = sections.map((section) => `
  <article class="card stack search-section search-section-ledger">
    <div class="cluster" style="justify-content: space-between;">
      <div>
        <span class="field-label">${escapeHtml(section.key || '')}</span>
        <h3>${escapeHtml(section.label || '')}</h3>
        <p class="text-muted">${escapeHtml(String(section.count || 0))} matching record${Number(section.count || 0) === 1 ? '' : 's'}</p>
      </div>
      <a class="btn-secondary" href="${escapeHtml(section.href || '#')}">Open Module</a>
    </div>
    <div class="search-result-list">
      ${(section.items || []).map((item) => `
        <a class="search-result-item" href="${escapeHtml(item.href || '#')}">
          <div class="search-result-copy">
            <strong>${escapeHtml(item.title || '')}</strong>
            <div class="text-muted">${escapeHtml(item.subtitle || '')}</div>
            <div class="search-result-meta">${escapeHtml(item.meta || '')}</div>
          </div>
          <span class="badge badge-info">${escapeHtml(item.badge || '')}</span>
        </a>
      `).join('')}
    </div>
  </article>
`).join('');
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Views/SearchViewTest.php tests/Integration/Http/ApiSearchHttpTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Views/SearchViewTest.php views/search/index.php public/assets/css/search.css public/assets/js/search.js
git commit -m "style: redesign global search command center"
```

### Task 5: Redesign Settings as an Operations Console

**Files:**
- Create: `tests/Views/SettingsViewTest.php`
- Modify: `views/settings/index.php`
- Modify: `public/assets/css/settings.css`
- Modify: `public/assets/js/settings.js`
- Reuse: `tests/Integration/Http/ApiSystemHttpTest.php`

- [ ] **Step 1: Write the failing settings view smoke test**

```php
<?php

declare(strict_types=1);

namespace Tests\Views;

final class SettingsViewTest extends ViewSmokeTestCase
{
    public function testSettingsPageRendersTheOperationsConsoleMarkers(): void
    {
        $html = $this->renderApp('settings.index', [
            'title' => 'Settings',
            'currentUser' => $this->defaultUser(),
            'canManageSystem' => true,
            'settingsMeta' => [
                'app_name' => 'Catarman Animal Shelter',
                'organization_name' => 'Catarman Dog Pound',
                'settings_storage_driver' => 'database',
                'app_env' => 'local',
                'app_url' => 'http://localhost:8000',
                'app_timezone' => 'Asia/Manila',
                'session_lifetime' => 60,
                'trusted_proxies' => '',
                'public_portal_enabled' => true,
                'maintenance_mode_enabled' => false,
                'maintenance_message' => '',
                'contact_email' => 'ops@example.test',
                'contact_phone' => '09171234567',
                'office_address' => 'Catarman, Northern Samar',
                'mail_delivery_mode' => 'log_only',
            ],
            'extraCss' => ['/assets/css/settings.css'],
            'extraJs' => ['/assets/js/settings.js'],
        ], '/settings');

        self::assertStringContainsString('settings-ops-hero', $html);
        self::assertStringContainsString('settings-zone-grid', $html);
        self::assertStringContainsString('settings-backup-ledger', $html);
        self::assertStringContainsString('settings-readiness-board', $html);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit tests/Views/SettingsViewTest.php`

Expected: FAIL because the settings page still uses the older stacked grid classes only.

- [ ] **Step 3: Write the minimal settings markup, CSS, and JS**

```php
<!-- views/settings/index.php -->
<section class="settings-ops-hero" id="settings-page">
    <div class="page-title-meta">
        <span class="badge badge-info">Runtime Operations</span>
        <h1>Settings</h1>
        <div class="breadcrumb">Home &gt; Settings</div>
        <p class="text-muted">Monitor runtime health, backup safety, maintenance posture, and deployment readiness from one operational console.</p>
    </div>
</section>

<section class="settings-zone-grid">
    <article class="card stack">
        <div class="settings-zone-header">
            <div>
                <span class="field-label">Health</span>
                <h3>System Health</h3>
            </div>
            <span class="badge" id="settings-maintenance-badge">Checking</span>
        </div>
        <div class="settings-health-grid" id="settings-health-grid"></div>
    </article>

    <article class="card stack settings-profile-console">
        <div class="settings-zone-header">
            <div>
                <span class="field-label">Configuration</span>
                <h3>Application Profile</h3>
            </div>
            <span class="badge <?= ($canManageSystem ?? false) ? 'badge-success' : 'badge-warning' ?>"><?= ($canManageSystem ?? false) ? 'Editable' : 'Read Only' ?></span>
        </div>
        <dl class="settings-profile-list">
            <div><dt>Application</dt><dd><?= htmlspecialchars((string) ($settingsMeta['app_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Organization</dt><dd><?= htmlspecialchars((string) ($settingsMeta['organization_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Settings Store</dt><dd><?= htmlspecialchars((string) ($settingsMeta['settings_storage_driver'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Environment</dt><dd><?= htmlspecialchars((string) ($settingsMeta['app_env'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Application URL</dt><dd><?= htmlspecialchars((string) ($settingsMeta['app_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Timezone</dt><dd><?= htmlspecialchars((string) ($settingsMeta['app_timezone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Session Lifetime</dt><dd><?= htmlspecialchars((string) ($settingsMeta['session_lifetime'] ?? 0), ENT_QUOTES, 'UTF-8') ?> minutes</dd></div>
            <div><dt>Trusted Proxies</dt><dd><?= htmlspecialchars((string) (($settingsMeta['trusted_proxies'] ?? '') !== '' ? $settingsMeta['trusted_proxies'] : 'Not configured'), ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Current Role</dt><dd><?= htmlspecialchars((string) ($currentUser['role_display_name'] ?? $currentUser['role_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>
    </article>
</section>

<section class="settings-zone-grid">
    <article class="card stack settings-backup-ledger">
        <div class="settings-zone-header">
            <div>
                <span class="field-label">Backups</span>
                <h3>Database Backups</h3>
            </div>
        </div>
        <div class="settings-inline-note">Restore requires typed confirmation and super administrator access.</div>
        <div class="users-table-wrap">
            <table class="users-table">
                <tbody id="settings-backups-body"></tbody>
            </table>
        </div>
    </article>

    <article class="card stack settings-readiness-board">
        <div class="settings-zone-header">
            <div>
                <span class="field-label">Readiness</span>
                <h3>Deployment Readiness</h3>
            </div>
        </div>
        <div class="settings-readiness-summary" id="settings-readiness-summary"></div>
        <div class="users-table-wrap">
            <table class="users-table">
                <tbody id="settings-readiness-body"></tbody>
            </table>
        </div>
    </article>
</section>
```

```css
/* public/assets/css/settings.css */
.settings-ops-hero {
  display: grid;
  gap: var(--space-4);
}

.settings-zone-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(320px, 1fr);
  gap: var(--space-5);
}

.settings-zone-header {
  display: flex;
  justify-content: space-between;
  gap: var(--space-4);
  align-items: start;
}

.settings-backup-ledger,
.settings-readiness-board {
  align-content: start;
}

.settings-health-card strong,
.settings-readiness-summary strong,
.settings-profile-list dt {
  font-family: var(--font-family-mono);
}
```

```js
// public/assets/js/settings.js
function healthCard(label, value) {
  return `
    <article class="settings-health-card">
      <span class="field-label">${escapeHtml(label)}</span>
      <strong>${escapeHtml(value)}</strong>
    </article>
  `;
}

function renderStatus(status) {
  const normalized = String(status || '').toLowerCase();
  const variant = normalized === 'completed'
    ? 'success'
    : normalized === 'failed'
      ? 'danger'
      : normalized === 'pass'
        ? 'success'
        : 'warning';

  return `<span class="badge badge-${variant}">${escapeHtml(status)}</span>`;
}
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Views/SettingsViewTest.php tests/Integration/Http/ApiSystemHttpTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Views/SettingsViewTest.php views/settings/index.php public/assets/css/settings.css public/assets/js/settings.js
git commit -m "style: redesign settings operations console"
```

### Task 6: Redesign the Public Adopter Landing Page

**Files:**
- Create: `tests/Views/PortalLandingViewTest.php`
- Modify: `views/portal/landing.php`
- Modify: `public/assets/css/portal.css`
- Modify: `public/assets/js/portal.js`

- [ ] **Step 1: Write the failing portal landing view smoke test**

```php
<?php

declare(strict_types=1);

namespace Tests\Views;

final class PortalLandingViewTest extends ViewSmokeTestCase
{
    public function testPortalLandingRendersTheCivicLedgerHeroAndTrustSections(): void
    {
        $html = $this->renderPublic('portal.landing', [
            'featuredAnimals' => $this->featuredAnimals(),
            'currentUser' => null,
        ], '/adopt');

        self::assertStringContainsString('portal-civic-hero', $html);
        self::assertStringContainsString('portal-trust-ribbon', $html);
        self::assertStringContainsString('portal-featured-ledger', $html);
        self::assertStringContainsString('data-carousel-track', $html);
    }
}
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit tests/Views/PortalLandingViewTest.php`

Expected: FAIL because the current portal landing page still uses the older hero and band classes.

- [ ] **Step 3: Write the minimal landing-page markup, CSS, and JS**

```php
<!-- views/portal/landing.php -->
<section class="portal-civic-hero">
    <div class="portal-civic-copy">
        <span class="badge badge-info">Adopter Portal</span>
        <div class="portal-landing-kicker">Catarman Animal Shelter</div>
        <h1>Adoption starts with a transparent, trust-first first step.</h1>
        <p class="text-muted">Browse available animals, learn the shelter process, and move into application only when you are ready.</p>
        <div class="cluster portal-landing-actions">
            <a class="btn-primary" href="<?= htmlspecialchars($heroPrimaryHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($heroPrimaryLabel, ENT_QUOTES, 'UTF-8') ?></a>
            <a class="btn-secondary" href="<?= htmlspecialchars($heroSecondaryHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($heroSecondaryLabel, ENT_QUOTES, 'UTF-8') ?></a>
        </div>
    </div>
    <article class="card portal-proof-card">
        <span class="field-label">What to expect</span>
        <h2>Clear requirements before you apply</h2>
        <div class="portal-process-grid">
            <div><strong>Shortlist an animal</strong><p class="text-muted">Start with profiles that fit your household.</p></div>
            <div><strong>Prepare your details</strong><p class="text-muted">Gather contact information and a valid ID.</p></div>
            <div><strong>Wait for staff review</strong><p class="text-muted">Qualified applications move into interviews and seminars.</p></div>
        </div>
    </article>
</section>

<section class="portal-trust-ribbon">
    <article class="portal-trust-ribbon-item"><strong><?= $featuredCount ?></strong><span>Featured animals ready to review now</span></article>
    <article class="portal-trust-ribbon-item"><strong>3 steps</strong><span>From shortlist to interview and seminar</span></article>
    <article class="portal-trust-ribbon-item"><strong>Online access</strong><span>Track your application anytime</span></article>
</section>

<section class="portal-featured-ledger stack">
    <div class="portal-section-header portal-landing-featured-header">
        <div>
            <span class="portal-landing-eyebrow">Featured animals</span>
            <h2>Start with a profile worth a closer look.</h2>
        </div>
        <a class="btn-secondary" href="/adopt/animals">View all animals</a>
    </div>
    <div class="portal-featured-carousel portal-featured-carousel-enhanced" data-featured-carousel>
        <div class="portal-featured-stage" data-carousel-stage>
            <div class="portal-featured-track" data-carousel-track>
                <?php foreach ($featuredAnimals as $index => $animal): ?>
                    <?php
                    $displayName = (string) ($animal['name'] ?: $animal['animal_id']);
                    $breedLabel = (string) ($animal['breed_name'] ?? 'Mixed Breed');
                    $metaLabel = trim(((string) $animal['gender']) . ' • ' . ((string) $animal['size']));
                    $detailHref = '/adopt/animals/' . (int) $animal['id'];
                    ?>
                    <article
                        class="portal-animal-card card portal-featured-slide"
                        data-carousel-slide
                        data-slide-index="<?= $index ?>"
                        data-slide-href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>"
                        role="link"
                        tabindex="<?= $index === 0 ? '0' : '-1' ?>"
                        aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"
                    >
                        <div class="portal-animal-photo portal-featured-photo">
                            <?php if (($animal['primary_photo_path'] ?? null) !== null): ?>
                                <img src="/<?= htmlspecialchars((string) $animal['primary_photo_path'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?>">
                            <?php else: ?>
                                <div class="portal-photo-fallback"><?= htmlspecialchars(substr($displayName, 0, 1), ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="portal-animal-card-body">
                            <div class="cluster" style="justify-content: space-between;">
                                <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
                                <span class="badge badge-success"><?= htmlspecialchars((string) $animal['species'], ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <p class="text-muted"><?= htmlspecialchars($breedLabel, ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="portal-card-meta"><?= htmlspecialchars($metaLabel !== '' ? $metaLabel : 'Shelter profile available', ENT_QUOTES, 'UTF-8') ?></p>
                            <a class="btn-secondary" href="<?= htmlspecialchars($detailHref, ENT_QUOTES, 'UTF-8') ?>">View profile</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
```

```css
/* public/assets/css/portal.css */
.portal-civic-hero {
  position: relative;
  display: grid;
  grid-template-columns: minmax(0, 1.3fr) minmax(320px, 0.9fr);
  gap: var(--space-8);
  padding: var(--space-8) 0 var(--space-10);
}

.portal-civic-copy {
  display: grid;
  gap: var(--space-5);
  padding: var(--space-8);
  border: 1px solid rgba(180, 83, 9, 0.18);
  border-radius: calc(var(--radius-xl) * 1.05);
  background: linear-gradient(155deg, rgba(15, 23, 42, 0.98), rgba(30, 58, 138, 0.92));
  color: #f8fafc;
}

.portal-trust-ribbon {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: var(--space-4);
  padding-bottom: var(--space-8);
}

.portal-featured-ledger,
.portal-proof-card,
.portal-trust-ribbon-item {
  background: linear-gradient(180deg, color-mix(in srgb, var(--color-bg-warm) 70%, white), color-mix(in srgb, var(--color-bg-elevated) 94%, white));
}
```

```js
// public/assets/js/portal.js
const setSlideState = (slide, state) => {
  slide.classList.toggle('is-active', state === 'active');
  slide.classList.toggle('is-preview', state === 'next');
  slide.classList.toggle('is-previous', state === 'previous');
};
```

- [ ] **Step 4: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Views/PortalLandingViewTest.php`

Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add tests/Views/PortalLandingViewTest.php views/portal/landing.php public/assets/css/portal.css public/assets/js/portal.js
git commit -m "style: redesign public portal landing page"
```

### Task 7: Lock the Docs and Run Final Verification

**Files:**
- Modify: `README.md`
- Modify: `PAGE_LAYOUTS.md`
- Modify: `tests/Views/AppShellViewTest.php`

- [ ] **Step 1: Add the final legacy-font regression assertion**

```php
public function testLayoutsDoNotLoadLegacyFiraFonts(): void
{
    $appHtml = $this->renderApp('dashboard.index');
    $portalHtml = $this->renderPublic('portal.landing', [
        'featuredAnimals' => $this->featuredAnimals(),
    ]);

    self::assertStringNotContainsString('Fira+Sans', $appHtml);
    self::assertStringNotContainsString('Fira+Code', $appHtml);
    self::assertStringNotContainsString('Fira+Sans', $portalHtml);
    self::assertStringNotContainsString('Fira+Code', $portalHtml);
}
```

- [ ] **Step 2: Run the test to verify it fails before the docs pass**

Run: `php vendor/bin/phpunit tests/Views/AppShellViewTest.php`

Expected: FAIL if any layout still points to the old font family URL.

- [ ] **Step 3: Update the runtime documentation**

```md
<!-- README.md -->
- The flagship UI now uses the Civic Ledger design system: Lexend for headings/navigation, Source Sans 3 for body copy, and JetBrains Mono for operational metadata.
- The first-pass redesigned surfaces are the shared internal shell, dashboard, global search, settings, and the public adopter landing page.
```

```md
<!-- PAGE_LAYOUTS.md -->
## Civic Ledger Flagship Pass

- Shared internal shell: command rail sidebar, command-surface topbar, and unified badges/buttons/inputs
- Dashboard: operational briefing hero, KPI ledger cards, action deck, and activity feed
- Global Search: command-first query band, module chip filters, and ledger-style result sections
- Settings: operations-console zoning for health, configuration, backups, maintenance, readiness, and notes
- Public landing: trust-first hero, proof ribbon, curated featured-animal ledger, and guided adoption flow
```

- [ ] **Step 4: Run the full verification set**

Run: `php vendor/bin/phpunit`

Expected: PASS

Run: `npm run tooling:check`

Expected: PASS

Run: `php -S localhost:8000 -t public`

Expected: PHP development server starts on `http://localhost:8000`

After the server starts, manually review `/dashboard`, `/search`, `/settings`, and `/adopt` at `375px`, `768px`, `1024px`, and `1440px`. Confirm that focus states are visible, sticky chrome does not cover content, and JetBrains Mono appears only in metadata surfaces.

- [ ] **Step 5: Commit**

```bash
git add README.md PAGE_LAYOUTS.md tests/Views/AppShellViewTest.php
git commit -m "docs: document Civic Ledger flagship rollout"
```

## Self-Review

### Spec coverage

- Shared shell covered by Tasks 1 and 2.
- Dashboard covered by Task 3.
- Global search covered by Task 4.
- Settings covered by Task 5.
- Public adopter landing covered by Task 6.
- Accessibility, responsive behavior, and typography regression checks covered by Tasks 1, 2, and 7.

### Placeholder scan

- No blocked planning shorthand remains.
- Each task includes exact file paths, concrete class names, concrete snippets, concrete commands, and a commit step.

### Type consistency

- Shared test helper names are fixed as `ViewSmokeTestCase`, `renderApp()`, `renderPublic()`, `defaultUser()`, and `featuredAnimals()`.
- Shared shell markers are fixed as `data-ui-theme="civic-ledger"`, `sidebar-rail-summary`, `sidebar-group-card`, `topbar-command-shell`, and `topbar-status-pill`.
- Page markers are fixed as `dashboard-briefing`, `search-command-shell`, `settings-ops-hero`, and `portal-civic-hero`.

## Execution Handoff

Plan complete and saved to `docs/superpowers/plans/2026-04-01-civic-ledger-flagship-ui-implementation.md`. Two execution options:

**1. Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

**2. Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

Which approach?
