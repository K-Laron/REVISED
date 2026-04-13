# Catarman Dog Pound & Animal Shelter Management System

## Current Runtime Snapshot

- Custom PHP 8.2 MVC application with a session-backed web UI and JSON API.
- [public/index.php](/C:/Users/TESS%20LARON/Desktop/REVISED/public/index.php) boots Dotenv, [config/app.php](/C:/Users/TESS%20LARON/Desktop/REVISED/config/app.php), maintenance checks, [routes/web.php](/C:/Users/TESS%20LARON/Desktop/REVISED/routes/web.php), and the modular API loader in [routes/api.php](/C:/Users/TESS%20LARON/Desktop/REVISED/routes/api.php).
- Current route surface:
  - `34` web routes
  - `126` production API routes across `15` route-module files
  - `1` extra debug-only API route: `POST /api/validate-test`
- Current schema baseline:
  - `39` tables in [database_schema.sql](/C:/Users/TESS%20LARON/Desktop/REVISED/database/database_schema.sql)
  - `6` tracked SQL migrations in [database/migrations](/C:/Users/TESS%20LARON/Desktop/REVISED/database/migrations)

## Civic Ledger UI System

- Authenticated pages use the Civic Ledger shell from [views/layouts/app.php](/C:/Users/TESS%20LARON/Desktop/REVISED/views/layouts/app.php).
- Typography:
  - `Lexend` for headings
  - `Source Sans 3` for primary UI copy
  - `JetBrains Mono` for technical labels and metrics
- The authenticated shell currently includes:
  - light and dark theme handoff on first paint
  - persistent sidebar scroll state during soft navigation
  - breadcrumb links with draft recovery for accidental navigation
  - unread-only notification dropdown behavior
- The dashboard is a bundled operations surface driven by `GET /api/dashboard/bootstrap` and rendered with the self-hosted Chart.js asset at [public/assets/vendor/chart.js/chart.umd.js](/C:/Users/TESS%20LARON/Desktop/REVISED/public/assets/vendor/chart.js/chart.umd.js).

## Stack

### Application Runtime

- PHP `>=8.2`
- MySQL-compatible relational database
- Custom router, middleware pipeline, and response helpers under [src/Core](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Core)
- Server-rendered PHP views under [views](/C:/Users/TESS%20LARON/Desktop/REVISED/views)

### Composer Packages

- `vlucas/phpdotenv`
- `chillerlan/php-qrcode`
- `tecnickcom/tcpdf`
- `phpmailer/phpmailer`
- `intervention/image`
- `monolog/monolog`
- Dev:
  - `phpunit/phpunit`
  - `fakerphp/faker`

### Optional Node Tooling

The app runtime does not require Node.js. The tracked Node dependencies are for documentation and asset tooling only.

- `beautiful-mermaid`
- `@resvg/resvg-js`
- `docx`

Validate optional tooling with:

```bash
npm run tooling:check
```

## Performance Diagnostics

- Request and query timing is instrumented through [src/Support/Performance/PerformanceProbe.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Support/Performance/PerformanceProbe.php).
- When `APP_PERFORMANCE_DEBUG=true` or `APP_DEBUG=true`, responses can emit:
  - `X-App-Request-Time-Ms`
  - `X-App-Query-Count`
  - `X-App-Database-Time-Ms`
- Local performance report script:

```bash
php scripts/performance/report.php "Manual Check"
```

- Dashboard first paint is aggregated and cached by [src/Services/DashboardService.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Services/DashboardService.php) through [src/Support/Cache/FileCacheStore.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Support/Cache/FileCacheStore.php).
- Pagination-heavy list endpoints use [src/Support/Pagination/PaginatedWindow.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Support/Pagination/PaginatedWindow.php) to avoid unnecessary count work when possible.

## Functional Areas

- Authentication and session lifecycle
- Dashboard operations and audit activity
- Animals, breeds, intake, photos, and QR handling
- Kennels, assignments, and maintenance logs
- Medical records, prescriptions, lab results, and vital signs
- Adoption workflow and public adopter portal
- Billing, payments, receipts, and fee schedule
- Inventory, categories, alerts, and stock movement
- User, role, session, and permission management
- Reports, exports, templates, and audit-trail review
- Notifications, global search, system settings, backups, and readiness checks

## Local Setup

1. Install Composer dependencies.

```bash
composer install
```

2. Install optional Node tooling if you need the docs or rendering helpers.

```bash
npm install
```

3. Create `.env` from [.env.example](/C:/Users/TESS%20LARON/Desktop/REVISED/.env.example) and configure MySQL, app URL, mail, and session settings.

4. Load the base schema and seed data.

```bash
mysql -u root -p < database/database_schema.sql
mysql -u root -p < database/seeders.sql
```

5. Apply tracked SQL migrations in [database/migrations](/C:/Users/TESS%20LARON/Desktop/REVISED/database/migrations).

6. Start the local PHP server.

```powershell
powershell -ExecutionPolicy Bypass -File scripts/start-app.ps1
```

[scripts/start-app.ps1](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/start-app.ps1) starts `php -S 127.0.0.1:8000 -t public` and opens `http://127.0.0.1:8000/adopt`.

### Generate Additional Local Animal Data

```bash
php scripts/seed_animals.php 80
```

### Generate Additional Local Activity Data

```bash
php scripts/seed_activity.php 250
```

### Recommended Local `.env` Defaults

- `APP_ENV=local`
- `APP_DEBUG=true`
- `APP_URL=http://127.0.0.1:8000`
- `APP_TIMEZONE=Asia/Manila`
- `SESSION_LIFETIME=120`

## Quick Start Scripts

- [scripts/start-app.ps1](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/start-app.ps1)
- [scripts/stop-app.ps1](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/stop-app.ps1)
- [scripts/start-app.cmd](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/start-app.cmd)
- [scripts/stop-app.cmd](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/stop-app.cmd)
- [scripts/start-app.vbs](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/start-app.vbs)
- [scripts/stop-app.vbs](/C:/Users/TESS%20LARON/Desktop/REVISED/scripts/stop-app.vbs)

`*.cmd` wrappers launch the PowerShell scripts directly. `*.vbs` wrappers launch the same scripts hidden so the PowerShell window stays out of the way when you double-click them from Windows.

## Verification

Run the release gate locally before pushing:

```powershell
powershell -ExecutionPolicy Bypass -File scripts/run-release-checks.ps1
```

This command runs route coverage, critical HTTP smoke tests, the full PHPUnit suite, the tracked Node tooling check, and the frontend smoke test.

## Storage and Generated Files

- `storage/backups/` for compressed SQL backups
- `storage/cache/` for runtime cache files including system settings and dashboard cache
- `storage/config/` for legacy settings fallback
- `storage/exports/` and `storage/pdfs/` for generated reports and billing/adoption documents
- `storage/logs/` for application logs
- `storage/runtime/` for local app-server state
- `storage/sessions/` for PHP session files
- `public/uploads/` for user-uploaded assets

## Security Model

- Server-side session auth for both the browser UI and the JSON API
- `HttpOnly` and `SameSite=Strict` session cookies via [src/Core/Session.php](/C:/Users/TESS%20LARON/Desktop/REVISED/src/Core/Session.php)
- CSRF protection on state-changing browser/API requests
- Route-level permission and role gates
- Rate limiting with database-backed tracking and file-store fallback
- Backup restore requires an exact typed confirmation and revalidated checksum

## Release Checklist

- Set a non-local `APP_URL`
- Disable `APP_DEBUG` in production
- Configure `APP_KEY`
- Configure `TRUSTED_PROXIES` when behind a proxy
- Configure SMTP or intentionally document a non-email recovery process
- Rotate the seeded admin password
- Verify writable `storage/` directories
- Apply all SQL migrations

## Docs Map

- [ARCHITECTURE.md](/C:/Users/TESS%20LARON/Desktop/REVISED/ARCHITECTURE.md)
- [API_ROUTES.md](/C:/Users/TESS%20LARON/Desktop/REVISED/API_ROUTES.md)
- [IMPLEMENTATION_GUIDE.md](/C:/Users/TESS%20LARON/Desktop/REVISED/IMPLEMENTATION_GUIDE.md)
- [VALIDATION_RULES.md](/C:/Users/TESS%20LARON/Desktop/REVISED/VALIDATION_RULES.md)
- [PAGE_LAYOUTS.md](/C:/Users/TESS%20LARON/Desktop/REVISED/docs/technical/PAGE_LAYOUTS.md)
- [PRD_Catarman_Dog_Pound.md](/C:/Users/TESS%20LARON/Desktop/REVISED/PRD_Catarman_Dog_Pound.md)
- [system_summary.md](/C:/Users/TESS%20LARON/Desktop/REVISED/system_summary.md)
- [llm_context.md](/C:/Users/TESS%20LARON/Desktop/REVISED/llm_context.md)
- [ROOT_LAYOUT.md](/C:/Users/TESS%20LARON/Desktop/REVISED/docs/technical/ROOT_LAYOUT.md)

## Notes on Historical Files

- The root Markdown files are the living system docs.
- Dated files under [docs](/C:/Users/TESS%20LARON/Desktop/REVISED/docs) are historical specs, plans, and measurement snapshots unless they explicitly say otherwise.
