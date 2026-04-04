# Operations Command Surface Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the authenticated shell into a better shift command surface by adding a dashboard action queue, client-side search presets, and notification triage without schema changes or new API routes.

**Architecture:** Keep version 1 schema-free and API-stable. Render dashboard attention data through the page controller and view payload, keep search presets in `localStorage`, and upgrade notification grouping on the client while reusing the current notification endpoints.

**Tech Stack:** PHP 8.2, server-rendered PHP views, vanilla JavaScript, localStorage, PHPUnit 10

---

### Task 1: Add A Dashboard Action Queue

**Files:**
- Create: `src/Services/Dashboard/DashboardActionQueueBuilder.php`
- Create: `tests/Services/Dashboard/DashboardActionQueueBuilderTest.php`
- Modify: `src/Controllers/DashboardController.php`
- Modify: `src/Services/DashboardService.php`
- Modify: `views/dashboard/index.php`
- Modify: `public/assets/js/dashboard.js`
- Modify: `public/assets/css/dashboard.css`

- [ ] **Step 1: Write the failing dashboard action queue test**

```php
<?php

declare(strict_types=1);

namespace Tests\Services\Dashboard;

use App\Services\Dashboard\DashboardActionQueueBuilder;
use PHPUnit\Framework\TestCase;

final class DashboardActionQueueBuilderTest extends TestCase
{
    public function testBuildReturnsPrioritizedCardsForOperationalAttention(): void
    {
        $builder = new DashboardActionQueueBuilder();

        $cards = $builder->build([
            'inventory' => ['low_stock_count' => 3, 'expiring_count' => 1],
            'billing' => ['overdue_count' => 4, 'overdue_balance' => 5200.00],
            'adoptions' => ['ready_for_completion' => 2, 'upcoming_interviews' => 1],
            'medical' => ['due_vaccinations' => 5, 'due_dewormings' => 2],
        ]);

        self::assertSame('billing', $cards[0]['module']);
        self::assertSame('/billing?payment_status=overdue', $cards[0]['href']);
        self::assertCount(4, $cards);
    }
}
```

- [ ] **Step 2: Run the test to confirm the builder is missing**

Run:

```powershell
php vendor/bin/phpunit tests/Services/Dashboard/DashboardActionQueueBuilderTest.php -v
```

Expected:

```text
FAILURES!
Class "App\Services\Dashboard\DashboardActionQueueBuilder" not found
```

- [ ] **Step 3: Implement the action queue builder and dashboard view wiring**

```php
<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

final class DashboardActionQueueBuilder
{
    public function build(array $signals): array
    {
        $cards = [];

        if (($signals['billing']['overdue_count'] ?? 0) > 0) {
            $cards[] = [
                'module' => 'billing',
                'priority' => 10,
                'title' => 'Overdue invoices',
                'count' => (int) $signals['billing']['overdue_count'],
                'detail' => 'Outstanding past-due balances require follow-up.',
                'href' => '/billing?payment_status=overdue',
            ];
        }

        if (($signals['medical']['due_vaccinations'] ?? 0) > 0 || ($signals['medical']['due_dewormings'] ?? 0) > 0) {
            $cards[] = [
                'module' => 'medical',
                'priority' => 9,
                'title' => 'Upcoming medical due items',
                'count' => (int) (($signals['medical']['due_vaccinations'] ?? 0) + ($signals['medical']['due_dewormings'] ?? 0)),
                'detail' => 'Vaccination and deworming follow-ups due within 30 days.',
                'href' => '/medical',
            ];
        }

        if (($signals['inventory']['low_stock_count'] ?? 0) > 0) {
            $cards[] = [
                'module' => 'inventory',
                'priority' => 8,
                'title' => 'Low stock items',
                'count' => (int) $signals['inventory']['low_stock_count'],
                'detail' => 'Inventory has reached or dropped below reorder thresholds.',
                'href' => '/inventory?status=low_stock',
            ];
        }

        if (($signals['adoptions']['ready_for_completion'] ?? 0) > 0) {
            $cards[] = [
                'module' => 'adoptions',
                'priority' => 7,
                'title' => 'Adoptions ready for completion',
                'count' => (int) $signals['adoptions']['ready_for_completion'],
                'detail' => 'Applications are waiting on final processing or payment confirmation.',
                'href' => '/adoptions?status=pending_payment',
            ];
        }

        usort($cards, static fn (array $left, array $right): int => $right['priority'] <=> $left['priority']);

        return $cards;
    }
}
```

```php
public function index(Request $request): Response
{
    $authUser = $request->attribute('auth_user');

    return $this->renderAppView('dashboard.index', [
        'user' => $authUser,
        'csrfToken' => CsrfMiddleware::token(),
        'title' => 'Dashboard',
        'extraCss' => ['/assets/css/dashboard.css'],
        'extraJs' => ['/assets/vendor/chart.js/chart.umd.js', '/assets/js/dashboard.js'],
        'dashboardActionQueue' => $this->dashboard->actionQueue(),
    ]);
}
```

```php
public function actionQueue(): array
{
    $inventory = (new InventoryService())->stats();
    $billing = (new BillingService())->stats();
    $adoptions = (new \App\Services\Adoption\AdoptionReadService(
        new \App\Models\AdoptionApplication(),
        new \App\Models\AdoptionInterview(),
        new \App\Models\AdoptionSeminar(),
        new \App\Models\AdoptionCompletion(),
        new \App\Services\Adoption\AdoptionStatusPolicy(),
        new \App\Services\Adoption\AdoptionBillingSummary()
    ))->pipelineStats();
    $medicalModel = new \App\Models\MedicalRecord();

    return (new \App\Services\Dashboard\DashboardActionQueueBuilder())->build([
        'inventory' => $inventory,
        'billing' => $billing,
        'adoptions' => $adoptions,
        'medical' => [
            'due_vaccinations' => count($medicalModel->dueVaccinations()),
            'due_dewormings' => count($medicalModel->dueDewormings()),
        ],
    ]);
}
```

```php
<section class="dashboard-action-queue card" data-dashboard-action-queue>
    <div class="dashboard-section-heading">
        <div>
            <span class="field-label">Immediate attention</span>
            <h3>Action Queue</h3>
        </div>
        <p class="text-muted">Open the highest-priority work without leaving the dashboard.</p>
    </div>
    <div id="dashboard-action-queue"></div>
</section>

<script id="dashboard-action-queue-data" type="application/json"><?= json_encode($dashboardActionQueue ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES) ?></script>
```

- [ ] **Step 4: Run the dashboard tests and verify the queue passes**

Run:

```powershell
php vendor/bin/phpunit tests/Services/Dashboard/DashboardActionQueueBuilderTest.php tests/Integration/Http/ApiDashboardHttpTest.php tests/Views/DashboardViewTest.php -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add src/Services/Dashboard src/Controllers/DashboardController.php src/Services/DashboardService.php views/dashboard/index.php public/assets/js/dashboard.js public/assets/css/dashboard.css tests/Services/Dashboard/DashboardActionQueueBuilderTest.php
git commit -m "feat: add dashboard action queue"
```

### Task 2: Add Client-Side Search Presets

**Files:**
- Create: `tests/Frontend/search-presets.test.js`
- Modify: `public/assets/js/search.js`
- Modify: `views/search/index.php`
- Modify: `public/assets/css/search.css`
- Modify: `tests/Views/SearchViewTest.php`

- [ ] **Step 1: Write the failing search preset test**

```javascript
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

const localStorage = new Map();
const context = {
  console,
  window: {
    localStorage: {
      getItem(key) { return localStorage.get(key) ?? null; },
      setItem(key, value) { localStorage.set(key, value); },
    },
    history: { replaceState() {} },
  },
  document: {
    addEventListener() {},
    getElementById() { return null; }
  }
};
context.globalThis = context;

vm.createContext(context);
vm.runInContext(fs.readFileSync('C:/Users/TESS LARON/Desktop/REVISED/public/assets/js/search.js', 'utf8'), context);

context.saveSearchPreset('low-stock', {
  modules: ['inventory'],
  inventory_status: 'low_stock',
  per_section: 5,
});

assert.strictEqual(context.loadSearchPresets()['low-stock'].inventory_status, 'low_stock');
```

- [ ] **Step 2: Run the preset test and confirm failure**

Run:

```powershell
node tests/Frontend/search-presets.test.js
```

Expected:

```text
TypeError: context.saveSearchPreset is not a function
```

- [ ] **Step 3: Implement search preset storage and UI**

```javascript
function loadSearchPresets() {
  try {
    return JSON.parse(window.localStorage.getItem('catarman.search.presets') || '{}');
  } catch (error) {
    return {};
  }
}

function saveSearchPreset(name, filters) {
  const presets = loadSearchPresets();
  presets[name] = filters;
  window.localStorage.setItem('catarman.search.presets', JSON.stringify(presets));
}

function applySearchPreset(form, preset) {
  if (!preset) {
    return;
  }

  form.elements.per_section.value = String(preset.per_section || 5);
  form.querySelectorAll('input[name="modules[]"]').forEach((checkbox) => {
    checkbox.checked = Array.isArray(preset.modules) && preset.modules.includes(checkbox.value);
  });
  if (form.elements.inventory_status) {
    form.elements.inventory_status.value = String(preset.inventory_status || '');
  }
}

globalThis.loadSearchPresets = loadSearchPresets;
globalThis.saveSearchPreset = saveSearchPreset;
globalThis.applySearchPreset = applySearchPreset;
```

```php
<section class="search-preset-dock card">
    <div class="search-secondary-header">
        <div>
            <span class="field-label">Preset runs</span>
            <h4>Saved Filters</h4>
        </div>
        <p class="text-muted">Store repeatable search combinations in this browser.</p>
    </div>
    <div class="search-preset-actions">
        <button class="btn-secondary" type="button" data-search-save-preset>Save current filters</button>
        <div id="search-preset-list"></div>
    </div>
</section>
```

- [ ] **Step 4: Run the preset tests and view checks**

Run:

```powershell
node tests/Frontend/search-presets.test.js
php vendor/bin/phpunit tests/Views/SearchViewTest.php -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add tests/Frontend/search-presets.test.js public/assets/js/search.js views/search/index.php public/assets/css/search.css tests/Views/SearchViewTest.php
git commit -m "feat: add client-side search presets"
```

### Task 3: Upgrade Notification Triage

**Files:**
- Modify: `views/partials/header.php`
- Modify: `public/assets/js/notifications.js`
- Modify: `public/assets/css/components.css`
- Modify: `tests/Views/AppShellViewTest.php`

- [ ] **Step 1: Write the failing triage rendering expectation**

```php
self::assertStringContainsString('data-notification-groups', $content);
self::assertStringContainsString('data-notification-empty', $content);
```

```javascript
function groupNotifications(items) {
  return items.reduce((groups, item) => {
    const severity = item.type === 'warning' ? 'Needs attention' : 'Updates';
    groups[severity] = groups[severity] || [];
    groups[severity].push(item);
    return groups;
  }, {});
}
```

- [ ] **Step 2: Run the shell view test and confirm failure**

Run:

```powershell
php vendor/bin/phpunit tests/Views/AppShellViewTest.php -v
```

Expected:

```text
FAILURES!
```

- [ ] **Step 3: Implement grouped notification triage**

```php
<div class="notification-groups" data-notification-groups aria-live="polite">
    <div class="notification-empty" data-notification-empty>Loading notifications.</div>
</div>
```

```javascript
function groupNotifications(items) {
  return items.reduce((groups, item) => {
    const bucket = item.type === 'warning' ? 'Needs attention' : 'Updates';
    groups[bucket] = groups[bucket] || [];
    groups[bucket].push(item);
    return groups;
  }, {});
}

function renderNotificationGroups(groups, root) {
  const entries = Object.entries(groups);
  if (entries.length === 0) {
    root.innerHTML = '<div class="notification-empty">No unread notifications.</div>';
    return;
  }

  root.innerHTML = entries.map(([label, items]) => `
    <section class="notification-group">
      <header class="notification-group-header">
        <strong>${escapeHtml(label)}</strong>
        <span class="badge badge-info">${items.length}</span>
      </header>
      <div class="notification-group-list">
        ${items.map((item) => `
          <button type="button" class="notification-item is-unread" data-notification-id="${item.id}">
            <div class="notification-item-meta">
              <strong>${escapeHtml(item.title || 'Notification')}</strong>
              <span>${formatNotificationDate(item.created_at)}</span>
            </div>
            <div>${escapeHtml(item.message || '')}</div>
          </button>
        `).join('')}
      </div>
    </section>
  `).join('');
}
```

- [ ] **Step 4: Run the notification view check**

Run:

```powershell
php vendor/bin/phpunit tests/Views/AppShellViewTest.php -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add views/partials/header.php public/assets/js/notifications.js public/assets/css/components.css tests/Views/AppShellViewTest.php
git commit -m "feat: group notifications for triage"
```
