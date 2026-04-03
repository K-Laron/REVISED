# Remaining Refactor Waves Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Finish the remaining prioritized refactors by centralizing browser helpers, moving validation catalogs out of target controllers, and decomposing the medical write path into focused collaborators.

**Architecture:** Keep all public routes and response shapes unchanged. Use characterization tests first, then introduce internal helper classes that controllers and `MedicalService` delegate to. Prefer small, mechanical wiring changes after the new helpers are covered by unit tests.

**Tech Stack:** PHP 8.2, PHPUnit 10, vanilla JavaScript, existing custom MVC framework

---

### Task 1: Finish Browser Helper Adoption

**Files:**
- Modify: `tests/Views/SharedBrowserHelpersTest.php`
- Modify: `public/assets/js/animals.js`
- Modify: `public/assets/js/billing.js`
- Modify: `public/assets/js/medical.js`
- Modify: `public/assets/js/adoptions.js`

- [ ] **Step 1: Write the failing test**

Add assertions that the remaining page scripts reference `window.CatarmanApi` and `window.CatarmanDom`.

- [ ] **Step 2: Run test to verify it fails**

Run: `php vendor/bin/phpunit tests/Views/SharedBrowserHelpersTest.php`
Expected: FAIL because the remaining page scripts still define local helper logic.

- [ ] **Step 3: Write minimal implementation**

Replace duplicated `extractError` and `escapeHtml` functions with aliases to the shared helper, and switch repeated JSON request flows to `window.CatarmanApi.request(...)` where that reduces duplication without changing request semantics.

- [ ] **Step 4: Run test to verify it passes**

Run: `php vendor/bin/phpunit tests/Views/SharedBrowserHelpersTest.php`
Expected: PASS

### Task 2: Extract Animal, Inventory, and Medical Validation

**Files:**
- Create: `src/Support/Validation/AnimalInputValidator.php`
- Create: `src/Support/Validation/InventoryInputValidator.php`
- Create: `src/Support/Validation/MedicalInputValidator.php`
- Create: `tests/Support/Validation/AnimalInputValidatorTest.php`
- Create: `tests/Support/Validation/InventoryInputValidatorTest.php`
- Create: `tests/Support/Validation/MedicalInputValidatorTest.php`
- Modify: `src/Controllers/AnimalController.php`
- Modify: `src/Controllers/InventoryController.php`
- Modify: `src/Controllers/MedicalController.php`

- [ ] **Step 1: Write the failing tests**

Cover the custom controller-only behaviors:
- animal stray intake requires `location_found`
- animal owner surrender requires `surrender_reason`
- animal photo validation limits count, type, and size
- inventory create/update/stock-change rules differ as expected
- medical lab attachments reject unsupported types and oversize files
- medical lab results require `test_name` when any content is present

- [ ] **Step 2: Run tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Support/Validation/AnimalInputValidatorTest.php tests/Support/Validation/InventoryInputValidatorTest.php tests/Support/Validation/MedicalInputValidatorTest.php`
Expected: FAIL because the validator classes do not exist yet.

- [ ] **Step 3: Write minimal implementation**

Move rule catalogs and custom validation branches out of controllers and into focused validator classes that return the existing `App\Helpers\Validator` instance plus any normalized payload needed by medical flows.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Support/Validation/AnimalInputValidatorTest.php tests/Support/Validation/InventoryInputValidatorTest.php tests/Support/Validation/MedicalInputValidatorTest.php`
Expected: PASS

### Task 3: Decompose the Medical Write Path

**Files:**
- Create: `src/Services/Medical/MedicalSubtypePersister.php`
- Create: `src/Services/Medical/MedicalSharedSectionPersister.php`
- Create: `src/Services/Medical/MedicalAttachmentManager.php`
- Create: `src/Services/Medical/MedicalAnimalStatusSynchronizer.php`
- Create: `tests/Services/Medical/MedicalSubtypePersisterTest.php`
- Create: `tests/Services/Medical/MedicalSharedSectionPersisterTest.php`
- Create: `tests/Services/Medical/MedicalAnimalStatusSynchronizerTest.php`
- Modify: `src/Services/MedicalService.php`

- [ ] **Step 1: Write the failing tests**

Add unit tests around collaborator responsibilities:
- subtype persistence delegates to the correct record model
- shared sections persist vital signs, prescriptions, and lab results from normalized payloads
- animal status sync promotes treatment/surgery/examination records and marks euthanasia as deceased

- [ ] **Step 2: Run tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Services/Medical/MedicalSubtypePersisterTest.php tests/Services/Medical/MedicalSharedSectionPersisterTest.php tests/Services/Medical/MedicalAnimalStatusSynchronizerTest.php`
Expected: FAIL because the collaborator classes do not exist yet.

- [ ] **Step 3: Write minimal implementation**

Keep `MedicalService` as the public facade, but move subtype resolution/persistence, shared-section persistence, attachment cleanup, and animal-status side effects into focused collaborators under `src/Services/Medical/`.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Services/Medical/MedicalSubtypePersisterTest.php tests/Services/Medical/MedicalSharedSectionPersisterTest.php tests/Services/Medical/MedicalAnimalStatusSynchronizerTest.php tests/Services/Medical/MedicalPayloadFactoryTest.php tests/Services/Medical/TreatmentInventorySynchronizerTest.php`
Expected: PASS

### Task 4: Wire Controllers and Service Facade, Then Verify

**Files:**
- Modify: `src/Controllers/AnimalController.php`
- Modify: `src/Controllers/InventoryController.php`
- Modify: `src/Controllers/MedicalController.php`
- Modify: `src/Services/MedicalService.php`

- [ ] **Step 1: Replace inline controller validation**

Wire controllers to the new validator classes and delete controller-local validation helpers that become redundant.

- [ ] **Step 2: Run targeted regressions**

Run:
`php vendor/bin/phpunit tests/Views/SharedBrowserHelpersTest.php tests/Support/Validation/AnimalInputValidatorTest.php tests/Support/Validation/InventoryInputValidatorTest.php tests/Support/Validation/MedicalInputValidatorTest.php tests/Services/Medical/MedicalSubtypePersisterTest.php tests/Services/Medical/MedicalSharedSectionPersisterTest.php tests/Services/Medical/MedicalAnimalStatusSynchronizerTest.php tests/Services/Medical/MedicalPayloadFactoryTest.php tests/Services/Medical/TreatmentInventorySynchronizerTest.php tests/Controllers/RendersViewsAdoptionTest.php tests/Controllers/DashboardControllerTest.php tests/Controllers/Concerns/InteractsWithApiTest.php tests/Views/AppShellViewTest.php`

Expected: PASS

- [ ] **Step 3: Run syntax verification**

Run `php -l` over each touched PHP file.
Expected: no syntax errors
