# Batch 1 Shared Browser Helpers Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reduce duplicated browser transport and escaping utilities while standardizing app/public layout plumbing and controller view rendering helpers.

**Architecture:** Add one shared core browser helper module loaded by both layouts, migrate duplicated page scripts onto that module without changing endpoint behavior, then switch app controllers that still manually call `Response::html(View::render(...))` onto the existing `RendersViews` trait. Keep the change narrowly scoped to existing conventions and current page/runtime structure.

**Tech Stack:** PHP 8.2, PHPUnit, server-rendered PHP views, vanilla browser JavaScript

---

### Task 1: Add Shared Browser Helper Asset

**Files:**
- Create: `public/assets/js/core/app-api.js`
- Modify: `views/layouts/app.php`
- Modify: `views/layouts/public.php`
- Test: `tests/Views/AppShellViewTest.php`

- [ ] **Step 1: Write the failing layout test**

Add assertions to `tests/Views/AppShellViewTest.php` that both authenticated and public layouts load `/assets/js/core/app-api.js`.

- [ ] **Step 2: Run the focused view test to verify it fails**

Run:

```powershell
php vendor/bin/phpunit tests/Views/AppShellViewTest.php
```

Expected: at least one failure because `/assets/js/core/app-api.js` is not yet loaded by the layouts.

- [ ] **Step 3: Add the shared helper asset and wire both layouts**

Create `public/assets/js/core/app-api.js` with shared `window.CatarmanApi` and `window.CatarmanDom` helpers:
- `request(url, options = {})`
- `parseResponse(response)`
- `extractError(payload, fallback)`
- `escapeHtml(value)`

Load the new script as a core asset in:
- `views/layouts/app.php`
- `views/layouts/public.php`

- [ ] **Step 4: Run the focused view test to verify it passes**

Run:

```powershell
php vendor/bin/phpunit tests/Views/AppShellViewTest.php
```

Expected: PASS for the new layout assertions and existing shell smoke coverage.

### Task 2: Migrate Page Scripts to Shared Browser Helpers

**Files:**
- Modify: `public/assets/js/users.js`
- Modify: `public/assets/js/settings.js`
- Modify: `public/assets/js/portal.js`
- Modify: `public/assets/js/inventory/inventory-formatters.js`
- Modify: `public/assets/js/kennels/kennel-utils.js`

- [ ] **Step 1: Write the failing script-level assertions**

Add focused assertions to existing view/controller smoke coverage or a new lightweight source test so the batch verifies:
- shared helper asset is available to page scripts
- `users.js` and `settings.js` no longer define local `apiRequest`
- utility wrappers in `inventory-formatters.js` and `kennel-utils.js` delegate to shared helpers

- [ ] **Step 2: Run the focused PHPUnit tests to verify failure**

Run:

```powershell
php vendor/bin/phpunit tests/Views/AppShellViewTest.php tests/Controllers/DashboardControllerTest.php
```

Expected: failures tied to the new helper usage assertions.

- [ ] **Step 3: Implement the script migrations**

Update:
- `public/assets/js/users.js`
- `public/assets/js/settings.js`
- `public/assets/js/portal.js`

So they call `window.CatarmanApi.request`, `window.CatarmanApi.parseResponse`, `window.CatarmanApi.extractError`, and `window.CatarmanDom.escapeHtml`.

Update:
- `public/assets/js/inventory/inventory-formatters.js`
- `public/assets/js/kennels/kennel-utils.js`

So `extractError` and `escapeHtml` delegate to the shared helpers instead of reimplementing them.

- [ ] **Step 4: Run the focused PHPUnit tests to verify they pass**

Run:

```powershell
php vendor/bin/phpunit tests/Views/AppShellViewTest.php tests/Controllers/DashboardControllerTest.php
```

Expected: PASS.

### Task 3: Adopt Existing View Rendering Trait Across App Controllers

**Files:**
- Modify: `src/Controllers/AnimalController.php`
- Modify: `src/Controllers/InventoryController.php`
- Modify: `src/Controllers/MedicalController.php`
- Modify: `src/Controllers/BillingController.php`
- Modify: `src/Controllers/DashboardController.php`
- Modify: `src/Controllers/UserController.php`
- Modify: `src/Controllers/ReportController.php`
- Modify: `src/Controllers/AdoptionController.php`

- [ ] **Step 1: Lock existing controller/view behavior with current coverage**

Use existing behavior tests as the characterization guard:
- `tests/Controllers/DashboardControllerTest.php`
- `tests/Views/AppShellViewTest.php`
- any currently passing smoke tests touched by these layouts/controllers

- [ ] **Step 2: Run the focused tests before refactoring**

Run:

```powershell
php vendor/bin/phpunit tests/Controllers/DashboardControllerTest.php tests/Views/AppShellViewTest.php
```

Expected: PASS on the pre-refactor baseline after Tasks 1-2.

- [ ] **Step 3: Replace manual `Response::html(View::render(...))` calls with `RendersViews`**

Add `use App\Controllers\Concerns\RendersViews;` and `use RendersViews;` where needed, then replace direct layout rendering calls with:
- `renderAppView(...)`
- keep explicit non-layout exception rendering only where it is materially different

- [ ] **Step 4: Re-run the focused tests**

Run:

```powershell
php vendor/bin/phpunit tests/Controllers/DashboardControllerTest.php tests/Views/AppShellViewTest.php
```

Expected: PASS with no rendered HTML regressions.

### Task 4: Batch Verification

**Files:**
- Modify: none required
- Test: `tests/Views/AppShellViewTest.php`
- Test: `tests/Controllers/DashboardControllerTest.php`
- Test: `tests/Controllers/Concerns/InteractsWithApiTest.php`

- [ ] **Step 1: Run the narrow verification set**

Run:

```powershell
php vendor/bin/phpunit tests/Views/AppShellViewTest.php tests/Controllers/DashboardControllerTest.php tests/Controllers/Concerns/InteractsWithApiTest.php
```

Expected: PASS.

- [ ] **Step 2: Review diff for scope control**

Run:

```powershell
git diff -- public/assets/js views/layouts src/Controllers tests
```

Expected: only helper extraction, layout script wiring, controller helper adoption, and targeted tests.
