# System Performance Pass Design

Date: April 2, 2026
Owner: Kenneth / Codex
Scope: authenticated app, dashboard APIs, search, repeated read paths, targeted database indexes

## Goal

Improve perceived and measured performance across the system by reducing:

- request latency on the authenticated app
- dashboard first-load time
- global search latency
- repeated database work on stable read paths
- unnecessary browser work during authenticated navigation

The pass must preserve the current route surface and user-facing behavior unless a performance-specific UI affordance is explicitly approved later.

## Non-Goals

- no framework rewrite
- no queue introduction
- no Redis or external cache dependency by default
- no speculative schema churn beyond targeted, evidence-backed indexes
- no redesign of business workflows

## Current Findings

### Backend

- [`src/Core/Database.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Core\Database.php) is intentionally thin and has no query timing, query counting, or request-level visibility.
- [`src/Controllers/DashboardController.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Controllers\DashboardController.php) exposes multiple small endpoints for one dashboard screen, forcing several backend executions per page load.
- [`src/Services/SearchService.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Services\SearchService.php) fans out across providers, and provider implementations use repeated `count + items` database patterns that likely amplify latency.
- Many services and models still make repeated direct database calls for list, detail, and summary paths, with no systematic measurement of hot queries.

### Frontend

- [`public/assets/js/dashboard.js`](C:\Users\TESS%20LARON\Desktop\REVISED\public\assets\js\dashboard.js) performs multiple API requests for one screen before the dashboard fully settles.
- Authenticated pages use a soft-navigation shell, but page-specific JS can still re-fetch aggressively unless explicitly optimized.
- There is no request timing or page-level instrumentation exposed for client-side diagnosis.

### Configuration and Caching

- [`config/app.php`](C:\Users\TESS%20LARON\Desktop\REVISED\config\app.php) already benefits from cached/bootstrap settings via [`src/Support/SystemSettings.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Support\SystemSettings.php), which is a solid foundation for further read optimization.
- The next caching wins are likely page summaries, dashboard bundles, and repeated metadata reads, not broad write-through caching of mutable operational records.

## Recommended Strategy

Use a measured, full-stack performance pass in five slices:

1. profiling and visibility
2. dashboard aggregation and client simplification
3. search and list-query reduction
4. targeted read caching
5. targeted database indexes

This is the preferred approach because it improves the main latency sources without introducing infrastructure complexity before measurement proves it is needed.

## Design

### 1. Profiling and Visibility

Add lightweight instrumentation to establish a baseline and measure gains after each optimization slice.

#### Proposed behavior

- Track per-request:
  - total request time
  - database query count
  - cumulative database time
  - slowest queries
- Enable profiling only in safe contexts such as local development, debug mode, or an explicit environment flag.
- Surface metrics through:
  - structured log output
  - optional response headers in debug mode
  - a simple internal summary utility if needed

#### Affected areas

- [`src/Core/Database.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Core\Database.php)
- request bootstrap / response emission paths
- selected controllers for endpoint-level timing summaries

#### Constraints

- profiling must add negligible overhead when disabled
- instrumentation cannot leak SQL or internals in production responses

### 2. Dashboard Aggregation and Client Simplification

Reduce dashboard load cost by replacing multiple first-paint calls with a single aggregated payload for the primary screen.

#### Proposed behavior

- Introduce one dashboard bootstrap endpoint or controller method that returns:
  - stats
  - intake chart
  - adoption chart
  - occupancy chart
  - medical chart
  - recent activity bundle
- Keep existing individual endpoints temporarily for compatibility or phased rollout.
- Update the dashboard client to prefer the bundled payload for first render.
- Keep page-level rendering logic in the browser, but remove redundant round-trips.

#### Affected areas

- [`src/Controllers/DashboardController.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Controllers\DashboardController.php)
- [`src/Services/DashboardService.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Services\DashboardService.php)
- [`public/assets/js/dashboard.js`](C:\Users\TESS%20LARON\Desktop\REVISED\public\assets\js\dashboard.js)
- dashboard route registration

#### Expected impact

- faster dashboard first-contentful data state
- fewer request headers, middleware passes, and controller constructions
- simpler client loading flow

### 3. Search and List-Query Reduction

Search and list pages are likely the largest recurring backend cost after dashboard load.

#### Proposed behavior

- review each search provider under [`src/Services/Search`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Services\Search) and eliminate duplicate work where possible
- reduce `count + items` pairs when the count is only needed for small section previews
- short-circuit search providers earlier for empty, low-value, or permission-filtered cases
- consider capped-result semantics for dashboard/global preview search where exact totals do not materially improve the experience
- profile key list pages and apply the same reduction patterns to high-traffic modules

#### Affected areas

- [`src/Services/SearchService.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Services\SearchService.php)
- provider classes under [`src/Services/Search/Providers`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Services\Search\Providers)
- list-oriented services/models identified by profiling

#### Constraints

- result ordering and permissions must remain unchanged
- total counts may only be relaxed where the UI does not rely on exact totals

### 4. Targeted Read Caching

Cache only stable or semi-stable read bundles where invalidation is straightforward.

#### Proposed behavior

- add short-lived or file-backed caching for:
  - dashboard summary bundles
  - stable metadata used repeatedly across authenticated screens
  - system settings or readiness-derived read models already aligned with the existing settings cache approach
- keep operational writes authoritative in the database
- invalidate aggressively on relevant updates rather than risking stale critical data

#### Affected areas

- [`src/Support/SystemSettings.php`](C:\Users\TESS%20LARON\Desktop\REVISED\src\Support\SystemSettings.php)
- dashboard read services
- selected metadata services
- optional small cache helper under `src/Support/` or `src/Services/`

#### Constraints

- no hidden stale-data risk on core operational records such as animal assignment, billing state, or medical actions
- cache boundaries must be obvious and testable

### 5. Targeted Database Indexes

Database indexes are explicitly in scope, but only after profiling and query review prove they are needed.

#### Proposed behavior

- identify the slowest queries from dashboard, search, and top list/detail screens
- map each slow query to its actual filter/sort predicates
- add only indexes that directly serve those predicates
- document each index with:
  - query shape served
  - expected improvement
  - affected table

#### Constraints

- no speculative indexing
- no broad index sweep across every table
- schema changes stay narrowly scoped to proven hotspots

## Delivery Plan

### Slice 1: Profiling baseline

Deliver:

- lightweight query timing and request metrics
- local/debug-only reporting path
- baseline measurements for dashboard, global search, and representative list screens

Success criteria:

- clear top offenders identified before any optimization work proceeds

### Slice 2: Dashboard performance

Deliver:

- aggregated dashboard payload
- simplified dashboard client loading
- before/after request and timing comparison

Success criteria:

- fewer HTTP requests on dashboard first load
- lower total dashboard response time

### Slice 3: Search and lists

Deliver:

- reduced provider query count
- trimmed work on list and preview paths
- evidence-backed optimizations for top measured screens

Success criteria:

- lower global search latency
- lower query count on targeted list/search flows

### Slice 4: Read caching

Deliver:

- safe caching for selected stable bundles
- invalidation rules and tests

Success criteria:

- repeated reads become cheaper without stale operational state bugs

### Slice 5: Indexes

Deliver:

- minimal index set for proven hotspots
- migration or schema update artifacts as required

Success criteria:

- measurable improvement on profiled slow queries

## Validation Strategy

Validation must be comparative and evidence-based.

### Before and after metrics

- request duration
- query count
- cumulative query time
- slowest query families

### Functional verification

- full PHPUnit suite
- focused regression coverage for dashboard and search paths
- targeted endpoint checks for any cached or aggregated response path

### Manual verification

- authenticated dashboard load
- global search responsiveness
- representative create/save workflow to ensure write paths are not regressed by caching or instrumentation

## Risks

- instrumentation that is too noisy or always-on could distort results
- caching the wrong read model could create stale operational data
- exact-count changes in search could accidentally alter current UX expectations if applied too broadly
- poorly chosen indexes could slow writes without helping real queries

## Decisions Already Made

- performance work should cover page loads, search/list screens, and save/create actions
- targeted database indexes are approved when profiling shows they are necessary
- no broad rewrite or new infrastructure should be introduced unless measurement proves it is needed

## Recommendation

Proceed with the five-slice measured performance pass in order, starting with profiling. Do not optimize blind. The first deliverable should be a baseline report that tells us which endpoints and query families deserve immediate attention.
