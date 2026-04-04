# System Improvement Roadmap Design

Date: April 4, 2026
Owner: Kenneth / Codex
Scope: delivery reliability, codebase maintainability, and operator workflow improvements for the live shelter application

## Goal

Improve the system in three deliberate lanes:

1. reduce release risk
2. reduce change cost in the highest-churn code
3. increase day-to-day operator leverage without broad rewrites

The work should follow the current architecture, avoid unnecessary dependencies, and preserve existing user-facing behavior unless a lane explicitly calls for a visible improvement.

## Recommendation

Execute the lanes in this order:

1. reliability
2. maintainability
3. operations

This is the recommended order because the repository is already broad enough that regressions are now the most expensive failure mode. Once the delivery path is safer, maintainability work can simplify the largest files without guessing. Only then should the system add new operator-facing workflow depth on top of cleaner seams.

## Current Findings

### Delivery Safety

- The repository currently has no `.github/workflows` directory, so there is no visible CI pipeline in-tree.
- PHPUnit coverage is already meaningful across controllers, services, integration, routes, support, and views.
- Frontend automated coverage exists but is still narrow. The only tracked JS test surface I found is [tests/Frontend/animals-inline-photo-upload.test.js](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Frontend/animals-inline-photo-upload.test.js).
- Route registration is partially guarded through [tests/Routes/ApiRouteRegistrationTest.php](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Routes/ApiRouteRegistrationTest.php), but there is no parallel web-route registration test and no documentation drift check around route counts and representative route coverage.

### Maintainability

- Frontend complexity is concentrated in a few large vanilla-JS files, especially [public/assets/js/animals.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/animals.js) and [public/assets/js/portal.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/portal.js).
- Backend workflow complexity is concentrated in [src/Services/AnimalService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/AnimalService.php), which mixes orchestration, photo handling, kennel transitions, normalization, and audit-related flow.
- The repository already has domain-specific validators such as [src/Support/Validation/AnimalInputValidator.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Support/Validation/AnimalInputValidator.php), so a generic validator refactor is lower leverage than splitting the domain hotspots first.
- The current worktree is active in the animal/photo path, so maintainability work in that area should land only after the current branch is stabilized or merged.

### Operations Surface

- The dashboard already has a strong read model in [src/Services/DashboardService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/DashboardService.php), including cached bootstrap data and recent activity.
- Search is already provider-based through [src/Services/SearchService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/SearchService.php), so operator workflow improvements can build on existing module/filter structure instead of inventing a second search layer.
- Notifications already exist as workflow alerts via [src/Services/NotificationService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/NotificationService.php) and [public/assets/js/notifications.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/notifications.js), but the current experience is still a basic unread inbox.
- The schema already supports actionable operational signals for low stock, overdue invoices, adoption pipeline state, and upcoming medical due dates through existing service/model queries.

## Lane 1: Reliability

### Problem

The repository has enough moving parts that local success is not enough. A regression in routes, permissions, page wiring, or high-value journeys can now hide behind unrelated work unless the repository standardizes how those checks run on every branch.

### Goal

Make the highest-value regression checks runnable in one command locally and in CI, using the existing stack first and avoiding dependency additions in the first pass.

### Design

#### 1. Route surface locking

- Add a dedicated web-route registration test alongside the existing API route test.
- Add a route-documentation sync test that validates route counts and representative endpoints against the living docs, especially [README.md](/C:/Users/TESS%20LARON/Desktop/REVISED/README.md), [ARCHITECTURE.md](/C:/Users/TESS%20LARON/Desktop/REVISED/ARCHITECTURE.md), and [API_ROUTES.md](/C:/Users/TESS%20LARON/Desktop/REVISED/API_ROUTES.md).
- Keep the route tests focused on behavior that should fail loudly when the route surface drifts: representative endpoints, module file presence, and published route counts.

#### 2. HTTP journey smoke tests

- Reuse the existing HTTP integration harness in [tests/Integration/Http/HttpIntegrationTestCase.php](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Integration/Http/HttpIntegrationTestCase.php).
- Cover the highest-value page journeys without new browser dependencies in phase 1:
  - guest login page
  - authenticated dashboard page
  - authenticated animal edit page
  - adopter-only public application page
- This is not full browser automation, but it gives reliable page-level smoke coverage now and stays inside the existing test stack.

#### 3. One release-check command

- Add a single PowerShell entrypoint under `scripts/` that runs the narrow release gate:
  - route tests
  - HTTP smoke tests
  - full PHPUnit suite
  - existing Node frontend smoke test
- Use that same command inside CI so local and remote verification stay aligned.

#### 4. In-repo CI

- Add a minimal GitHub Actions workflow that installs PHP and Node, runs Composer install and npm install, then executes the release-check script.
- Keep CI intentionally small. This lane is about verification consistency, not matrix expansion or long-running acceptance suites.

#### 5. Approval-gated follow-up

- If Kenneth later approves a new dev dependency, add browser automation as a separate follow-up lane.
- Do not block phase 1 on Playwright or another new browser runner.

### Affected Areas

- [tests/Routes/ApiRouteRegistrationTest.php](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Routes/ApiRouteRegistrationTest.php)
- [tests/bootstrap.php](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/bootstrap.php)
- [tests/Integration/Http/HttpIntegrationTestCase.php](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Integration/Http/HttpIntegrationTestCase.php)
- [tests/Frontend/animals-inline-photo-upload.test.js](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Frontend/animals-inline-photo-upload.test.js)
- [README.md](/C:/Users/TESS%20LARON/Desktop/REVISED/README.md)
- [ARCHITECTURE.md](/C:/Users/TESS%20LARON/Desktop/REVISED/ARCHITECTURE.md)
- [API_ROUTES.md](/C:/Users/TESS%20LARON/Desktop/REVISED/API_ROUTES.md)
- new workflow and script files under `.github/` and `scripts/`

### Non-Goals

- no new app behavior
- no schema changes
- no browser dependency in phase 1
- no broad test rewrite

## Lane 2: Maintainability

### Problem

The most expensive files to change are large because they combine too many responsibilities. This slows future work, enlarges review scope, and makes targeted testing harder than it should be.

### Goal

Split the highest-churn files into smaller units with one clear job each, without changing routes, API contracts, or page behavior.

### Design

#### 1. Stabilize current behavior before refactoring

- Expand focused tests around the animal photo UI and animal workflow service before extracting code.
- Keep current end-to-end behavior pinned while moving code behind smaller boundaries.

#### 2. Split animal frontend behavior by concern

- Introduce a `public/assets/js/animals/` folder with small files for:
  - shared DOM helpers
  - photo collection behavior
  - form behavior
  - scanner behavior
  - timeline behavior
  - bootstrapping
- Update [src/Controllers/AnimalController.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Controllers/AnimalController.php) to load the new script set through a single helper method.
- Keep [public/assets/js/animals.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/animals.js) as a compatibility bridge until the new asset set is verified, so this lane avoids deleting files mid-pass.

#### 3. Split animal backend orchestration by responsibility

- Extract collaborators from [src/Services/AnimalService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/AnimalService.php):
  - `AnimalPayloadFactory` for normalized payload construction
  - `AnimalPhotoManager` for upload, reorder, delete, and primary-photo logic
  - `AnimalKennelCoordinator` for assignment and transition rules
- Reduce the top-level service to orchestration and audit flow.

#### 4. Split portal frontend behavior by page

- Introduce a `public/assets/js/portal/` folder with separate files for:
  - registration form behavior
  - application form behavior
  - animal list/detail interactions
  - bootstrapping
- Update [src/Controllers/AdopterPortalController.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Controllers/AdopterPortalController.php) to load the new page asset list through a helper.
- Keep [public/assets/js/portal.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/portal.js) as a compatibility file in the same way as the animal lane.

#### 5. Defer generic validator surgery

- Do not start by refactoring [src/Helpers/Validator.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Helpers/Validator.php).
- Revisit that helper only after the domain hotspots above have stabilized and only if its remaining complexity is still a meaningful bottleneck.

### Affected Areas

- [public/assets/js/animals.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/animals.js)
- [src/Controllers/AnimalController.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Controllers/AnimalController.php)
- [src/Services/AnimalService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/AnimalService.php)
- [public/assets/js/portal.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/portal.js)
- [src/Controllers/AdopterPortalController.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Controllers/AdopterPortalController.php)
- targeted tests under [tests/Frontend](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Frontend), [tests/Services](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Services), and [tests/Integration](/C:/Users/TESS%20LARON/Desktop/REVISED/tests/Integration)

### Non-Goals

- no behavior redesign
- no route changes
- no API payload changes
- no file deletion in the first pass

## Lane 3: Operations

### Problem

The dashboard, search, and notifications already expose data, but they still behave more like separate read surfaces than a coordinated operator command system.

### Goal

Make the existing staff workflow faster by surfacing the highest-priority work directly in the authenticated shell, while staying schema-free and API-stable in version 1.

### Design

#### 1. Dashboard action queue

- Extend the dashboard page, not the dashboard API, with an action queue rendered from the page controller.
- Build the queue from data the system already knows how to compute:
  - low stock and expiring inventory via [src/Services/InventoryService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/InventoryService.php)
  - due vaccinations and dewormings via [src/Models/MedicalRecord.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Models/MedicalRecord.php)
  - ready-for-completion and upcoming adoption stages via [src/Services/Adoption/AdoptionReadService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/Adoption/AdoptionReadService.php)
  - overdue billing totals via [src/Services/BillingService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/BillingService.php)
- Render each queue item as a count, urgency label, short explanation, and deep link into the relevant module.

#### 2. Search presets

- Add client-side search presets in [public/assets/js/search.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/search.js) and [views/search/index.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/search/index.php).
- Store presets in `localStorage` so version 1 requires no schema change.
- Provide sensible preset entries for real staff work:
  - under-medical-care animals
  - low-stock inventory
  - overdue billing
  - pending-review adoptions

#### 3. Notification triage

- Upgrade the notification panel from a flat unread list into grouped triage buckets on the client.
- Derive severity and module grouping from existing notification `type`, `title`, and `link` data.
- Keep the existing notification API stable and reuse the current unread-count polling path.

#### 4. Keep version 1 approval-safe

- No schema change in version 1.
- No new API route in version 1.
- No change to public adopter flows in version 1.
- If persistent shared presets or user-owned dashboard layouts are desired later, treat them as a separate schema-approved follow-up.

### Affected Areas

- [src/Controllers/DashboardController.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Controllers/DashboardController.php)
- [src/Services/DashboardService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/DashboardService.php)
- [views/dashboard/index.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/dashboard/index.php)
- [public/assets/js/dashboard.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/dashboard.js)
- [public/assets/css/dashboard.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/dashboard.css)
- [views/search/index.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/search/index.php)
- [public/assets/js/search.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/search.js)
- [public/assets/css/search.css](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/css/search.css)
- [views/partials/header.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/partials/header.php)
- [public/assets/js/notifications.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/js/notifications.js)

### Non-Goals

- no schema change in version 1
- no new dashboard API contract in version 1
- no queue workers
- no redesign of underlying business workflows

## Recommended Execution Order

### Phase 1: Reliability

Ship CI, route locking, journey smoke tests, and one release-check command first.

### Phase 2: Maintainability

Once the current animal/photo branch is stable, split the animal and portal hotspots and extract the animal service collaborators.

### Phase 3: Operations

After the maintainability pass has created cleaner seams, build the dashboard action queue, search presets, and notification triage.

## Success Metrics

### Reliability

- one command runs the release gate locally
- CI fails on route drift or broken high-value journeys
- the current Node frontend smoke test is part of the standard gate

### Maintainability

- the largest hotspot files are materially smaller
- extracted collaborators have direct tests
- controller asset loading is clearer and less repetitive

### Operations

- staff can jump from dashboard to high-priority work in one click
- high-value search states can be recalled without re-entering filters
- notifications are easier to triage than a flat unread list

## Risks and Mitigations

- Reliability risk: adding too many checks at once could slow iteration.
  Mitigation: keep phase 1 focused on route tests, journey smoke tests, and the current suite.

- Maintainability risk: refactoring large files while the worktree is still active could create merge churn.
  Mitigation: start maintainability only after the current animal/photo work is settled.

- Operations risk: dashboard improvements can accidentally become an API change.
  Mitigation: keep version 1 server-rendered from the dashboard page controller instead of altering the existing JSON bootstrap contract.

## Decisions Already Made

- The first work should reduce release risk, not add more feature surface.
- Maintainability work should target the biggest hotspots before touching generic utilities.
- Operations version 1 should stay schema-free and API-stable.

## Recommendation

Approve this roadmap as the execution order for the next improvement cycle:

1. reliability hardening
2. hotspot maintainability refactor
3. operator command-surface improvements

The implementation work should proceed through three separate plans so each lane can be executed, reviewed, and validated independently.
