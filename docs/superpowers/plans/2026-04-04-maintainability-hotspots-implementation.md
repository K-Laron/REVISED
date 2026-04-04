# Maintainability Hotspots Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reduce change cost in the largest workflow hotspots by splitting animal and portal frontend code and extracting focused collaborators from the animal service without changing behavior.

**Architecture:** Preserve the no-build vanilla-JS setup by loading multiple page scripts through controller `extraJs` arrays instead of introducing a bundler. On the backend, reduce `AnimalService` to orchestration by moving payload, photo, and kennel responsibilities into dedicated collaborators with direct tests.

**Tech Stack:** PHP 8.2, vanilla JavaScript, PHPUnit 10, Node.js

---

### Task 1: Freeze Animal Flow Behavior Before Refactoring

**Files:**
- Create: `tests/Frontend/animals-form-bindings.test.js`
- Modify: `tests/Frontend/animals-inline-photo-upload.test.js`
- Modify: `tests/Integration/Animal/AnimalServiceIntegrationTest.php`

- [ ] **Step 1: Write the failing frontend and integration tests for the current animal behavior**

```javascript
const assert = require('assert');
const fs = require('fs');
const vm = require('vm');

function loadAnimalsScript(context) {
  const source = fs.readFileSync('C:/Users/TESS LARON/Desktop/REVISED/public/assets/js/animals.js', 'utf8');
  vm.createContext(context);
  vm.runInContext(source, context);
}

async function testIntakeConditionalFieldVisibility() {
  const speciesSelect = { value: 'Dog', addEventListener() {} };
  const breedSelect = { options: [], selectedOptions: [{ hidden: false }], value: '', addEventListener() {} };
  const intakeTypeSelect = { value: 'Stray', listeners: {}, addEventListener(type, listener) { this.listeners[type] = listener; } };
  const locationField = { hidden: true, querySelectorAll() { return []; } };
  const surrenderField = { hidden: true, querySelectorAll() { return []; } };
  const broughtBySection = { hidden: true, querySelectorAll() { return []; } };
  const authoritySection = { hidden: true, querySelectorAll() { return []; } };
  const form = {
    addEventListener() {},
    querySelector(selector) {
      if (selector === '[data-breed-species]') return speciesSelect;
      if (selector === '[data-breed-select]') return breedSelect;
      if (selector === '[data-intake-type]') return intakeTypeSelect;
      if (selector === '[data-location-found-field]') return locationField;
      if (selector === '[data-surrender-reason-field]') return surrenderField;
      if (selector === '[data-brought-by-section]') return broughtBySection;
      if (selector === '[data-authority-section]') return authoritySection;
      return null;
    }
  };
  const document = {
    addEventListener() {},
    getElementById(id) {
      return id === 'animal-form' ? form : null;
    },
    querySelectorAll() { return []; }
  };
  const context = { document, window: { CatarmanApi: {}, CatarmanDom: { escapeHtml: String } }, console };
  context.globalThis = context;

  loadAnimalsScript(context);
  context.bindAnimalForm();
  await intakeTypeSelect.listeners.change();

  assert.strictEqual(locationField.hidden, false);
}

testIntakeConditionalFieldVisibility().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
```

```php
public function testUpdatingAnimalReleasesPreviousKennelBeforeAssigningTheNextOne(): void
{
    \App\Core\Database::execute(
        'INSERT INTO kennels (kennel_code, zone, size_category, allowed_species, status, created_by) VALUES
         ("K-A01", "Building A", "Medium", "Dog", "Available", 1),
         ("K-A02", "Building A", "Medium", "Dog", "Available", 1)'
    );
    $user = $this->createUser('super_admin');
    $animal = $this->createAnimal();
    $firstKennel = \App\Core\Database::fetch('SELECT id FROM kennels WHERE kennel_code = "K-A01"');
    $secondKennel = \App\Core\Database::fetch('SELECT id FROM kennels WHERE kennel_code = "K-A02"');

    $service = new \App\Services\AnimalService();
    $request = $this->makeRequest([], [], [], [], ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/animals/' . $animal['id']]);
    $service->update((int) $animal['id'], ['kennel_id' => $firstKennel['id']], (int) $user['id'], $request);
    $updated = $service->update((int) $animal['id'], ['kennel_id' => $secondKennel['id']], (int) $user['id'], $request);

    self::assertSame((int) $secondKennel['id'], (int) $updated['current_kennel']['id']);
}
```

- [ ] **Step 2: Run the tests to confirm they fail before extraction**

Run:

```powershell
node tests/Frontend/animals-form-bindings.test.js
php vendor/bin/phpunit tests/Integration/Animal/AnimalServiceIntegrationTest.php -v
```

Expected:

```text
Error: bindAnimalForm is not defined
FAILURES!
```

- [ ] **Step 3: Expand the current test surface until it passes on the monolithic files**

```javascript
async function testIntakeConditionalFieldVisibility() {
  const speciesSelect = { value: 'Dog', addEventListener() {} };
  const breedSelect = { options: [], selectedOptions: [{ hidden: false }], value: '', addEventListener() {} };
  const intakeTypeSelect = {
    value: 'Stray',
    listeners: {},
    addEventListener(type, listener) {
      this.listeners[type] = listener;
    }
  };
  const locationField = {
    hidden: true,
    querySelectorAll() {
      return [];
    }
  };
  const surrenderField = { hidden: true, querySelectorAll() { return []; } };
  const broughtBySection = { hidden: true, querySelectorAll() { return []; } };
  const authoritySection = { hidden: true, querySelectorAll() { return []; } };
  const form = {
    addEventListener() {},
    querySelector(selector) {
      if (selector === '[data-breed-species]') return speciesSelect;
      if (selector === '[data-breed-select]') return breedSelect;
      if (selector === '[data-intake-type]') return intakeTypeSelect;
      if (selector === '[data-location-found-field]') return locationField;
      if (selector === '[data-surrender-reason-field]') return surrenderField;
      if (selector === '[data-brought-by-section]') return broughtBySection;
      if (selector === '[data-authority-section]') return authoritySection;
      return null;
    }
  };
  const document = {
    addEventListener() {},
    getElementById(id) {
      return id === 'animal-form' ? form : null;
    },
    querySelectorAll() {
      return [];
    }
  };

  const context = {
    console,
    document,
    window: {
      CatarmanApi: { request() {}, extractError() { return ''; } },
      CatarmanDom: { escapeHtml: (value) => String(value) }
    }
  };
  context.globalThis = context;

  loadAnimalsScript(context);
  context.bindAnimalForm();
  await intakeTypeSelect.listeners.change();

  assert.strictEqual(locationField.hidden, false);
}
```

```php
public function testUpdatingAnimalReleasesPreviousKennelBeforeAssigningTheNextOne(): void
{
    \App\Core\Database::execute(
        'INSERT INTO kennels (kennel_code, zone, size_category, allowed_species, status, created_by) VALUES
         ("K-A01", "Building A", "Medium", "Dog", "Available", 1),
         ("K-A02", "Building A", "Medium", "Dog", "Available", 1)'
    );
    $user = $this->createUser('super_admin');
    $animal = $this->createAnimal();
    $firstKennel = \App\Core\Database::fetch('SELECT id FROM kennels WHERE kennel_code = "K-A01"');
    $secondKennel = \App\Core\Database::fetch('SELECT id FROM kennels WHERE kennel_code = "K-A02"');
    $request = $this->makeRequest([], [], [], [], ['REQUEST_METHOD' => 'PUT', 'REQUEST_URI' => '/api/animals/' . $animal['id']]);

    $service = new \App\Services\AnimalService();
    $service->update((int) $animal['id'], ['kennel_id' => $firstKennel['id']], (int) $user['id'], $request);
    $updated = $service->update((int) $animal['id'], ['kennel_id' => $secondKennel['id']], (int) $user['id'], $request);

    self::assertSame((int) $secondKennel['id'], (int) $updated['current_kennel']['id']);
    self::assertCount(2, $updated['kennel_history']);
}
```

- [ ] **Step 4: Run the expanded tests and verify they pass**

Run:

```powershell
node tests/Frontend/animals-inline-photo-upload.test.js
node tests/Frontend/animals-form-bindings.test.js
php vendor/bin/phpunit tests/Integration/Animal/AnimalServiceIntegrationTest.php -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add tests/Frontend/animals-inline-photo-upload.test.js tests/Frontend/animals-form-bindings.test.js tests/Integration/Animal/AnimalServiceIntegrationTest.php
git commit -m "test: freeze animal workflow behavior before refactor"
```

### Task 2: Split Animal Frontend Assets By Responsibility

**Files:**
- Create: `public/assets/js/animals/shared.js`
- Create: `public/assets/js/animals/photo-collection.js`
- Create: `public/assets/js/animals/form.js`
- Create: `public/assets/js/animals/scanner.js`
- Create: `public/assets/js/animals/timeline.js`
- Create: `public/assets/js/animals/index.js`
- Modify: `public/assets/js/animals.js`
- Modify: `src/Controllers/AnimalController.php`

- [ ] **Step 1: Write the failing loader expectation**

```php
private function animalPageScripts(): array
{
    return [
        'https://unpkg.com/html5-qrcode',
        '/assets/js/animals/shared.js',
        '/assets/js/animals/photo-collection.js',
        '/assets/js/animals/form.js',
        '/assets/js/animals/scanner.js',
        '/assets/js/animals/timeline.js',
        '/assets/js/animals/index.js',
    ];
}
```

```php
self::assertStringContainsString('/assets/js/animals/shared.js', $content);
self::assertStringContainsString('/assets/js/animals/photo-collection.js', $content);
self::assertStringContainsString('/assets/js/animals/form.js', $content);
self::assertStringContainsString('/assets/js/animals/scanner.js', $content);
self::assertStringContainsString('/assets/js/animals/timeline.js', $content);
self::assertStringContainsString('/assets/js/animals/index.js', $content);
```

- [ ] **Step 2: Run the focused tests to confirm the helper does not exist yet**

Run:

```powershell
php vendor/bin/phpunit tests/Controllers/RendersViewsAdoptionTest.php -v
```

Expected:

```text
FAILURES!
Failed asserting that '<script src="/assets/js/animals/shared.js'
is contained in the rendered output
```

- [ ] **Step 3: Extract the animal frontend modules and controller asset helper**

```javascript
window.CatarmanAnimals = window.CatarmanAnimals || {};

window.CatarmanAnimals.shared = {
  apiRequest(url, options = {}) {
    return window.CatarmanApi.request(url, options);
  },
  extractError(payload) {
    return window.CatarmanApi.extractError(payload);
  },
  escapeHtml(value) {
    return window.CatarmanDom.escapeHtml(value);
  }
};
```

```javascript
(() => {
  const { apiRequest, extractError } = window.CatarmanAnimals.shared;

  function bindInlineUploadForm(form) {
    const input = form.querySelector('[data-photo-upload-input]');
    const preview = form.querySelector('[data-photo-upload-preview]');
    if (!input) {
      return;
    }

    input.addEventListener('change', () => {
      renderPhotoPreview(preview, input.files);
    });
  }

  function bindPhotoUpload() {
    document.querySelectorAll('.animal-photo-upload-form').forEach((form) => {
      bindInlineUploadForm(form);
    });
  }

  function bindAnimalPhotoCollections() {
    document.querySelectorAll('[data-animal-photo-collection]').forEach((collection) => {
      bindAnimalPhotoCollection(collection);
    });
  }

  window.CatarmanAnimals.photoCollection = {
    bindPhotoUpload,
    bindAnimalPhotoCollections
  };
})();
```

```javascript
document.addEventListener('DOMContentLoaded', () => {
  window.CatarmanAnimals.form?.bindAnimalForm?.();
  window.CatarmanAnimals.form?.bindAnimalList?.();
  window.CatarmanAnimals.form?.bindAnimalTabs?.();
  window.CatarmanAnimals.form?.bindStatusForm?.();
  window.CatarmanAnimals.photoCollection?.bindPhotoUpload?.();
  window.CatarmanAnimals.photoCollection?.bindAnimalPhotoCollections?.();
  window.CatarmanAnimals.scanner?.bindScanner?.();
  window.CatarmanAnimals.timeline?.loadTimeline?.();
});
```

```php
private function animalPageScripts(): array
{
    return [
        'https://unpkg.com/html5-qrcode',
        '/assets/js/animals/shared.js',
        '/assets/js/animals/photo-collection.js',
        '/assets/js/animals/form.js',
        '/assets/js/animals/scanner.js',
        '/assets/js/animals/timeline.js',
        '/assets/js/animals/index.js',
    ];
}
```

- [ ] **Step 4: Run the frontend and view tests to verify behavior still passes**

Run:

```powershell
node tests/Frontend/animals-inline-photo-upload.test.js
node tests/Frontend/animals-form-bindings.test.js
php vendor/bin/phpunit tests/Views/AnimalsViewTest.php tests/Controllers/RendersViewsAdoptionTest.php -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add public/assets/js/animals/shared.js public/assets/js/animals/photo-collection.js public/assets/js/animals/form.js public/assets/js/animals/scanner.js public/assets/js/animals/timeline.js public/assets/js/animals/index.js public/assets/js/animals.js src/Controllers/AnimalController.php
git commit -m "refactor: split animal frontend assets by concern"
```

### Task 3: Extract AnimalService Collaborators

**Files:**
- Create: `src/Services/Animals/AnimalPayloadFactory.php`
- Create: `src/Services/Animals/AnimalPhotoManager.php`
- Create: `src/Services/Animals/AnimalKennelCoordinator.php`
- Create: `tests/Services/Animals/AnimalPayloadFactoryTest.php`
- Create: `tests/Services/Animals/AnimalPhotoManagerTest.php`
- Create: `tests/Services/Animals/AnimalKennelCoordinatorTest.php`
- Modify: `src/Services/AnimalService.php`
- Modify: `tests/Integration/Animal/AnimalServiceIntegrationTest.php`

- [ ] **Step 1: Write the failing unit tests against the extracted classes**

```php
<?php

declare(strict_types=1);

namespace Tests\Services\Animals;

use App\Services\Animals\AnimalPayloadFactory;
use PHPUnit\Framework\TestCase;

final class AnimalPayloadFactoryTest extends TestCase
{
    public function testBuildForCreateNormalizesOptionalFields(): void
    {
        $factory = new AnimalPayloadFactory();

        $payload = $factory->buildForCreate([
            'species' => 'Dog',
            'gender' => 'Male',
            'size' => 'Medium',
            'intake_type' => 'Stray',
            'intake_date' => '2026-04-04',
            'condition_at_intake' => 'Healthy',
            'temperament' => 'Friendly',
            'weight_kg' => '12.40',
        ], 7);

        self::assertSame(7, $payload['created_by']);
        self::assertSame(12.4, $payload['weight_kg']);
    }
}
```

- [ ] **Step 2: Run the extracted-class tests and confirm failure**

Run:

```powershell
php vendor/bin/phpunit tests/Services/Animals/AnimalPayloadFactoryTest.php tests/Services/Animals/AnimalPhotoManagerTest.php tests/Services/Animals/AnimalKennelCoordinatorTest.php -v
```

Expected:

```text
FAILURES!
Class "App\Services\Animals\AnimalPayloadFactory" not found
```

- [ ] **Step 3: Implement the collaborator extraction**

```php
<?php

declare(strict_types=1);

namespace App\Services\Animals;

use App\Helpers\IdGenerator;
use App\Helpers\Sanitizer;

final class AnimalPayloadFactory
{
    public function buildForCreate(array $data, int $userId): array
    {
        return array_merge($this->basePayload($data, $userId), [
            'animal_id' => IdGenerator::next('animal_id'),
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function buildForUpdate(array $data, int $userId): array
    {
        return array_merge($this->basePayload($data, $userId), [
            'updated_by' => $userId,
        ]);
    }

    private function basePayload(array $data, int $userId): array
    {
        return [
            'species' => trim((string) $data['species']),
            'gender' => trim((string) $data['gender']),
            'size' => trim((string) $data['size']),
            'intake_type' => trim((string) $data['intake_type']),
            'intake_date' => trim((string) $data['intake_date']),
            'condition_at_intake' => trim((string) $data['condition_at_intake']),
            'temperament' => trim((string) $data['temperament']),
            'weight_kg' => ($data['weight_kg'] ?? '') !== '' ? round((float) $data['weight_kg'], 2) : null,
            'authority_contact' => Sanitizer::phone($data['authority_contact'] ?? null),
            'brought_by_contact' => Sanitizer::phone($data['brought_by_contact'] ?? null),
            'updated_by' => $userId,
        ];
    }
}
```

```php
final class AnimalService
{
    public function __construct(
        ?Animal $animals = null,
        ?Breed $breeds = null,
        ?AnimalPhoto $photos = null,
        ?QrCodeService $qrCodes = null,
        ?AuditService $audit = null,
        ?AnimalPayloadFactory $payloads = null,
        ?AnimalPhotoManager $photoManager = null,
        ?AnimalKennelCoordinator $kennels = null
    ) {
        $this->animals = $animals ?? new Animal();
        $this->breeds = $breeds ?? new Breed();
        $this->photos = $photos ?? new AnimalPhoto();
        $this->qrCodes = $qrCodes ?? new QrCodeService();
        $this->audit = $audit ?? new AuditService();
        $this->payloads = $payloads ?? new AnimalPayloadFactory();
        $this->photoManager = $photoManager ?? new AnimalPhotoManager($this->photos);
        $this->kennels = $kennels ?? new AnimalKennelCoordinator($this->animals);
    }
}
```

- [ ] **Step 4: Run the collaborator tests and the animal integration test**

Run:

```powershell
php vendor/bin/phpunit tests/Services/Animals tests/Integration/Animal/AnimalServiceIntegrationTest.php -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add src/Services/Animals src/Services/AnimalService.php tests/Services/Animals tests/Integration/Animal/AnimalServiceIntegrationTest.php
git commit -m "refactor: extract animal workflow collaborators"
```

### Task 4: Split Portal Frontend Assets By Page

**Files:**
- Create: `public/assets/js/portal/register.js`
- Create: `public/assets/js/portal/apply.js`
- Create: `public/assets/js/portal/listing.js`
- Create: `public/assets/js/portal/index.js`
- Modify: `public/assets/js/portal.js`
- Modify: `src/Controllers/AdopterPortalController.php`

- [ ] **Step 1: Write the failing portal boot contract**

```php
self::assertStringContainsString('/assets/js/portal/register.js', $content);
self::assertStringContainsString('/assets/js/portal/apply.js', $content);
self::assertStringContainsString('/assets/js/portal/listing.js', $content);
self::assertStringContainsString('/assets/js/portal/index.js', $content);
```

- [ ] **Step 2: Run the portal view tests and confirm the helper is missing**

Run:

```powershell
php vendor/bin/phpunit tests/Views/PortalLandingViewTest.php -v
```

Expected:

```text
FAILURES!
Failed asserting that '<script src="/assets/js/portal/register.js'
is contained in the rendered output
```

- [ ] **Step 3: Implement the portal split without changing page selectors**

```javascript
window.CatarmanPortal = window.CatarmanPortal || {};

window.CatarmanPortal.register = {
  bind() {
    const form = document.getElementById('portal-register-form');
    if (!form) {
      return;
    }

    Array.from(form.querySelectorAll('input, textarea')).forEach((field) => {
      field.addEventListener('blur', () => validateRegisterField(form, field.name));
    });
  }
};
```

```javascript
window.CatarmanPortal.apply = {
  bind() {
    const form = document.getElementById('portal-apply-form');
    if (!form) {
      return;
    }

    const errorNode = document.getElementById('portal-apply-errors');
    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      errorNode.hidden = true;

      const formData = new FormData(form);
      const csrfToken = formData.get('_token');

      await fetch('/api/adopt/apply', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: formData
      });
    });
  }
};
```

```javascript
document.addEventListener('DOMContentLoaded', () => {
  window.CatarmanPortal.register?.bind?.();
  window.CatarmanPortal.apply?.bind?.();
  window.CatarmanPortal.listing?.bind?.();
});
```

```php
private function portalScripts(): array
{
    return [
        '/assets/js/portal/register.js',
        '/assets/js/portal/apply.js',
        '/assets/js/portal/listing.js',
        '/assets/js/portal/index.js',
    ];
}
```

- [ ] **Step 4: Run the portal tests and verify they pass**

Run:

```powershell
php vendor/bin/phpunit tests/Views/PortalLandingViewTest.php tests/Controllers/ValidationRefactorAdoptionTest.php -v
```

Expected:

```text
OK
```

- [ ] **Step 5: Commit**

```powershell
git add public/assets/js/portal/register.js public/assets/js/portal/apply.js public/assets/js/portal/listing.js public/assets/js/portal/index.js public/assets/js/portal.js src/Controllers/AdopterPortalController.php
git commit -m "refactor: split portal frontend assets by page"
```
