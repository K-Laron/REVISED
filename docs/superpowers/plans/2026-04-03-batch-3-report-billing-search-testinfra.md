# Final Backlog Refactor Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish the remaining queued refactors by splitting dossier/report responsibilities, isolating billing side effects, moving search metadata closer to providers, and extracting reusable HTTP/integration test scaffolding.

**Architecture:** Keep controllers and routes unchanged. Introduce focused collaborators under existing service and test namespaces, then reduce the old façade classes to orchestration only. Prefer source-adoption and collaborator unit tests over broad rewrites.

**Tech Stack:** PHP 8.2, PHPUnit 10, vanilla JS, existing MVC app, static `Database` access

---

### Task 1: Split Report Generation from Animal Dossier Assembly

**Files:**
- Create: `src/Services/Reports/AnimalDossierService.php`
- Create: `src/Services/Reports/ReportRange.php`
- Create: `tests/Services/Reports/AnimalDossierServiceTest.php`
- Create: `tests/Services/Reports/ReportRefactorAdoptionTest.php`
- Modify: `src/Services/ReportService.php`

- [ ] **Step 1: Write the failing tests**

Add tests that:
- `AnimalDossierService` aggregates related adoption, billing, and audit records from injected collaborators
- `ReportService` source now delegates dossier work to `AnimalDossierService`

- [ ] **Step 2: Run tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Services/Reports/AnimalDossierServiceTest.php tests/Services/Reports/ReportRefactorAdoptionTest.php`
Expected: FAIL because the new dossier service does not exist yet and `ReportService` still owns the logic directly.

- [ ] **Step 3: Write minimal implementation**

Move `animalDossier()` record assembly into `AnimalDossierService`, keep `ReportService` as the public façade, and extract date-range normalization into a small helper value object so report generation methods stop owning date defaulting inline.

- [ ] **Step 4: Run tests to verify it passes**

Run: `php vendor/bin/phpunit tests/Services/Reports/AnimalDossierServiceTest.php tests/Services/Reports/ReportRefactorAdoptionTest.php`
Expected: PASS

### Task 2: Decouple Billing Writes from Documents and Notifications

**Files:**
- Create: `src/Services/Billing/BillingDocumentManager.php`
- Create: `src/Services/Billing/BillingNotificationDispatcher.php`
- Create: `tests/Services/Billing/BillingDocumentManagerTest.php`
- Create: `tests/Services/Billing/BillingNotificationDispatcherTest.php`
- Create: `tests/Services/Billing/BillingRefactorAdoptionTest.php`
- Modify: `src/Services/BillingService.php`

- [ ] **Step 1: Write the failing tests**

Add tests that:
- `BillingDocumentManager` updates invoice and receipt paths via injected dependencies
- `BillingNotificationDispatcher` delegates billing-clerk notifications to `NotificationService`
- `BillingService` source delegates PDF/notification work to the extracted collaborators

- [ ] **Step 2: Run tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Services/Billing/BillingDocumentManagerTest.php tests/Services/Billing/BillingNotificationDispatcherTest.php tests/Services/Billing/BillingRefactorAdoptionTest.php`
Expected: FAIL because the collaborators do not exist yet.

- [ ] **Step 3: Write minimal implementation**

Move invoice/receipt PDF regeneration into `BillingDocumentManager` and role-notification calls into `BillingNotificationDispatcher`, keeping the transaction logic in `BillingService`.

- [ ] **Step 4: Run tests to verify it passes**

Run: `php vendor/bin/phpunit tests/Services/Billing/BillingDocumentManagerTest.php tests/Services/Billing/BillingNotificationDispatcherTest.php tests/Services/Billing/BillingRefactorAdoptionTest.php tests/Services/Billing/InvoiceComputationTest.php`
Expected: PASS

### Task 3: Move Search Metadata Closer to Providers

**Files:**
- Create: `src/Services/Search/SearchModuleCatalog.php`
- Create: `tests/Services/Search/SearchModuleCatalogTest.php`
- Modify: `src/Services/Search/AbstractSearchProvider.php`
- Modify: `src/Services/Search/Providers/AnimalsSearchProvider.php`
- Modify: `src/Services/Search/Providers/MedicalSearchProvider.php`
- Modify: `src/Services/Search/Providers/AdoptionsSearchProvider.php`
- Modify: `src/Services/Search/Providers/BillingSearchProvider.php`
- Modify: `src/Services/Search/Providers/InventorySearchProvider.php`
- Modify: `src/Services/Search/Providers/UsersSearchProvider.php`
- Modify: `src/Services/Search/SearchFilterCatalog.php`
- Modify: `src/Services/SearchService.php`
- Modify: `tests/Services/Search/SearchServiceTest.php`

- [ ] **Step 1: Write the failing tests**

Add tests that:
- provider metadata builds secondary filters through `SearchModuleCatalog`
- legacy status aliases continue to normalize correctly
- available secondary filters still match accessible providers

- [ ] **Step 2: Run tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Services/Search/SearchModuleCatalogTest.php tests/Services/Search/SearchServiceTest.php`
Expected: FAIL because the catalog does not exist yet.

- [ ] **Step 3: Write minimal implementation**

Add optional metadata methods to `AbstractSearchProvider`, define module-specific filter metadata in each provider, and shrink `SearchFilterCatalog` so it normalizes and exposes metadata built from the provider set rather than a global hard-coded table.

- [ ] **Step 4: Run tests to verify it passes**

Run: `php vendor/bin/phpunit tests/Services/Search/SearchModuleCatalogTest.php tests/Services/Search/SearchServiceTest.php`
Expected: PASS

### Task 4: Extract HTTP and Fixture Test Bootstrapping

**Files:**
- Create: `tests/Integration/Support/HttpTestEnvironment.php`
- Create: `tests/Integration/Support/DatabaseFixtureFactory.php`
- Create: `tests/Integration/Support/HttpTestEnvironmentTest.php`
- Create: `tests/Integration/Support/DatabaseFixtureFactoryAdoptionTest.php`
- Modify: `tests/Integration/Http/HttpIntegrationTestCase.php`
- Modify: `tests/Integration/DatabaseIntegrationTestCase.php`

- [ ] **Step 1: Write the failing tests**

Add tests that:
- `HttpTestEnvironment` produces app config and request URIs consistently
- `DatabaseIntegrationTestCase` source delegates fixture creation to `DatabaseFixtureFactory`

- [ ] **Step 2: Run tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Integration/Support/HttpTestEnvironmentTest.php tests/Integration/Support/DatabaseFixtureFactoryAdoptionTest.php`
Expected: FAIL because the support classes do not exist yet.

- [ ] **Step 3: Write minimal implementation**

Move request/app/header bootstrapping into `HttpTestEnvironment` and move domain fixture builders into `DatabaseFixtureFactory`, with the existing base test cases delegating to them.

- [ ] **Step 4: Run tests to verify it passes**

Run: `php vendor/bin/phpunit tests/Integration/Support/HttpTestEnvironmentTest.php tests/Integration/Support/DatabaseFixtureFactoryAdoptionTest.php`
Expected: PASS

### Task 5: Verify the Final Refactor Wave

**Files:**
- Modify: all files above as needed

- [ ] **Step 1: Run focused regressions**

Run:
`php vendor/bin/phpunit tests/Services/Reports/AnimalDossierServiceTest.php tests/Services/Reports/ReportRefactorAdoptionTest.php tests/Services/Billing/BillingDocumentManagerTest.php tests/Services/Billing/BillingNotificationDispatcherTest.php tests/Services/Billing/BillingRefactorAdoptionTest.php tests/Services/Billing/InvoiceComputationTest.php tests/Services/Search/SearchModuleCatalogTest.php tests/Services/Search/SearchServiceTest.php tests/Integration/Support/HttpTestEnvironmentTest.php tests/Integration/Support/DatabaseFixtureFactoryAdoptionTest.php tests/Views/SharedBrowserHelpersTest.php tests/Controllers/ValidationRefactorAdoptionTest.php tests/Controllers/RendersViewsAdoptionTest.php tests/Controllers/DashboardControllerTest.php tests/Controllers/Concerns/InteractsWithApiTest.php tests/Views/AppShellViewTest.php tests/Support/Validation/AnimalInputValidatorTest.php tests/Support/Validation/InventoryInputValidatorTest.php tests/Support/Validation/MedicalInputValidatorTest.php tests/Services/Medical/MedicalSubtypePersisterTest.php tests/Services/Medical/MedicalSharedSectionPersisterTest.php tests/Services/Medical/MedicalAnimalStatusSynchronizerTest.php tests/Services/Medical/MedicalPayloadFactoryTest.php tests/Services/Medical/TreatmentInventorySynchronizerTest.php`

Expected: PASS

- [ ] **Step 2: Run syntax verification**

Run `php -l` over each touched PHP file.
Expected: no syntax errors
