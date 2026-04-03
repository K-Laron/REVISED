# Optional Cleanup Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add safer constructor seams to the main domain services, centralize the remaining duplicated page-formatting helpers, and verify the refactored UI through a live browser pass.

**Architecture:** Preserve existing call sites by adding optional constructor dependencies with sensible defaults, then add one small browser-side formatter bundle that the remaining page scripts consume. Keep verification focused: narrow PHPUnit coverage for the new seams plus a few live browser checks against representative pages.

**Tech Stack:** PHP 8.2, PHPUnit 10, vanilla JS, custom PHP MVC app, Playwright browser automation

---

### Task 1: Add Constructor Injection Seams to Main Domain Services

**Files:**
- Create: `tests/Services/ServiceConstructorInjectionTest.php`
- Modify: `src/Services/UserService.php`
- Modify: `src/Services/AnimalService.php`
- Modify: `src/Services/InventoryService.php`
- Modify: `src/Services/BillingService.php`
- Modify: `src/Services/MedicalService.php`
- Modify: `src/Services/ReportService.php`

- [ ] **Step 1: Write the failing test**

Add a constructor-injection adoption test that instantiates the main services with mocked collaborators and asserts the injected instances are stored on the service.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit tests/Services/ServiceConstructorInjectionTest.php`
Expected: FAIL because the services do not yet accept injected dependencies.

- [ ] **Step 3: Write minimal implementation**

Add optional typed constructor parameters to each target service and default them to the existing concrete instances so current controllers and other call sites remain unchanged.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php vendor/bin/phpunit tests/Services/ServiceConstructorInjectionTest.php`
Expected: PASS

### Task 2: Centralize Remaining Page Formatting Helpers

**Files:**
- Create: `public/assets/js/core/app-formatters.js`
- Modify: `views/layouts/app.php`
- Modify: `views/layouts/public.php`
- Modify: `public/assets/js/billing.js`
- Modify: `public/assets/js/medical.js`
- Modify: `public/assets/js/adoptions.js`
- Modify: `tests/Views/SharedBrowserHelpersTest.php`

- [ ] **Step 1: Write the failing test**

Extend the existing shared-browser-helper coverage so layouts must load the formatter asset and the remaining page scripts must reference `window.CatarmanFormatters` instead of local duplicate helpers.

- [ ] **Step 2: Run the test to verify it fails**

Run: `php vendor/bin/phpunit tests/Views/SharedBrowserHelpersTest.php`
Expected: FAIL because the new formatter asset and page-script adoption do not exist yet.

- [ ] **Step 3: Write minimal implementation**

Add shared `currency`, `titleCase`, `formatDate`, `formatDateTime`, and `toDateTimeLocal` helpers under one global namespace and replace the remaining inline page-script duplicates with calls into that helper.

- [ ] **Step 4: Run the test to verify it passes**

Run: `php vendor/bin/phpunit tests/Views/SharedBrowserHelpersTest.php`
Expected: PASS

### Task 3: Verify the Cleanup Layer

**Files:**
- Modify: files above as needed

- [ ] **Step 1: Run focused regressions**

Run:
`php vendor/bin/phpunit tests/Services/ServiceConstructorInjectionTest.php tests/Views/SharedBrowserHelpersTest.php tests/Controllers/ValidationRefactorAdoptionTest.php tests/Controllers/RendersViewsAdoptionTest.php tests/Services/Billing/BillingDocumentManagerTest.php tests/Services/Billing/BillingNotificationDispatcherTest.php tests/Services/Billing/BillingRefactorAdoptionTest.php tests/Services/Search/SearchModuleCatalogTest.php tests/Services/Search/SearchServiceTest.php tests/Services/Reports/AnimalDossierServiceTest.php tests/Services/Reports/ReportRefactorAdoptionTest.php tests/Integration/Support/HttpTestEnvironmentTest.php tests/Integration/Support/DatabaseFixtureFactoryAdoptionTest.php`
Expected: PASS

- [ ] **Step 2: Run syntax verification**

Run `php -l` on each touched PHP file.
Expected: no syntax errors

- [ ] **Step 3: Run live browser verification**

Verify a representative public page and authenticated page load through the shared browser assets, and confirm at least one formatter-using page renders without console/runtime failures.
