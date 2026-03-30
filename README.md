# Catarman Dog Pound & Animal Shelter Management System

This repository contains the current implemented CDP&ASMS runtime as of March 27, 2026. The application is a custom PHP 8.2 MVC system for shelter intake, medical records, adoptions, billing, inventory, reporting, search, user administration, system settings, backups, and the public adopter portal.

## Current Runtime Snapshot

- Authentication uses server-side PHP sessions plus persisted `user_sessions` validation.
- The app currently exposes 34 web routes and 126 API routes.
- The runtime schema is 42 tables: 39 base tables from `database_schema.sql` plus 3 additional tables introduced by the current 5 SQL migrations.
- PDF exports are generated on the server with `TCPDF`.
- Dashboard charts use Chart.js loaded at runtime from `https://cdn.jsdelivr.net/npm/chart.js`.
- System settings persist in MySQL when the `system_settings` table is available and fall back to `storage/config/system_settings.json` only as a legacy safety path.

## Stack

### Application Runtime

- PHP 8.2+
- MySQL 8.0+
- Composer PSR-4 autoloading under `App\\`
- Vanilla HTML, CSS, and JavaScript views served from PHP templates

### Composer Packages

- `vlucas/phpdotenv` for environment loading
- `chillerlan/php-qrcode` for QR code generation
- `tecnickcom/tcpdf` for report, receipt, invoice, and dossier PDFs
- `phpmailer/phpmailer` for optional SMTP delivery
- `intervention/image` for image handling
- `monolog/monolog` for structured logging support

### Optional Node Tooling

The Node dependencies in `package.json` are not required to run the PHP app itself. They are used for local documentation and manuscript tooling:

- `beautiful-mermaid`
- `@resvg/resvg-js`
- `docx`

## Functional Areas

The implemented system currently covers:

- Authentication, profile management, password reset, forced password change, and session invalidation
- Executive dashboard with KPI cards, charts, and recent activity
- Animal intake, profile editing, photo uploads, status updates, soft delete and restore, QR generation, and timeline views
- Medical records with procedure-specific forms for vaccination, surgery, examination, treatment, deworming, and euthanasia
- Shared medical subsections for vital signs, prescriptions, and lab results
- Kennel assignment, release, history, maintenance logging, and occupancy statistics
- Adoption applications, interviews, seminars, completion, certificates, and adopter self-service workflows
- Billing with invoices, payments, receipts, fee schedule management, and financial stats
- Inventory categories, item management, stock movements, alerts, and transaction history
- User management, role and permission updates, and per-user session management
- Reports with CSV and PDF exports, saved templates, census summaries, and animal dossiers
- Notifications, global search, deployment readiness checks, maintenance mode, system backups, and backup restore confirmation
- Public adopter portal with landing page, animal browsing, registration, application submission, and application tracking

## Local Setup

1. Copy `.env.example` to `.env`.
2. Set your database credentials and local `APP_URL`.
3. Install PHP dependencies:

```powershell
composer install
```

4. Import the base schema and seeders:

```powershell
mysql -u root -p -e "CREATE DATABASE catarman_shelter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p catarman_shelter < database_schema.sql
mysql -u root -p catarman_shelter < seeders.sql
```

5. Apply the current SQL migrations in order:

```powershell
mysql -u root -p catarman_shelter < database\migrations\2026_03_25_000001_add_usernames_to_users.sql
mysql -u root -p catarman_shelter < database\migrations\2026_03_26_000001_add_animal_detail_fields.sql
mysql -u root -p catarman_shelter < database\migrations\2026_03_26_000002_add_medical_vital_signs.sql
mysql -u root -p catarman_shelter < database\migrations\2026_03_26_000003_add_medical_prescriptions.sql
mysql -u root -p catarman_shelter < database\migrations\2026_03_26_000004_add_medical_lab_results.sql
```

6. Start the PHP server:

```powershell
php -S localhost:8000 -t public
```

7. Run the PHPUnit suite:

```powershell
php vendor/bin/phpunit
```

The suite includes unit coverage plus database-backed adoption integration tests. Use a populated local database and valid `.env` connection settings before running it.

### Generate Additional Local Animal Data

Use the dev-only CLI seeder to add randomized animal records to your current local database:

```powershell
php scripts/seed_animals.php 80
```

Optional: pass a reproducible random seed with `--seed=<number>`.

### Generate Additional Local Activity Data

Use the dev-only CLI activity seeder to add randomized adoption workflow and medical records to your current local database:

```powershell
php scripts/seed_activity.php --medical=45 --adoptions=18 --seminars=3
```

Optional: pass a reproducible random seed with `--seed=<number>`.

### Recommended Local `.env` Defaults

- `APP_URL=http://localhost:8000`
- `APP_TIMEZONE=Asia/Manila`
- `APP_DEBUG=true`
- `TRUSTED_PROXIES=`
- `SESSION_LIFETIME=60`

For local-only password recovery testing, `mail_delivery_mode=log_only` is acceptable. Reset URLs will be logged instead of sent.

## Quick Start Scripts

- `start-app.vbs` starts the PHP server in the background and opens `/adopt`.
- `stop-app.vbs` stops the background server tracked in `storage/runtime/app-server.json`.
- Runtime logs for the VBScript helpers are written under `storage/runtime/`.

## Storage and Generated Files

The runtime expects these writable directories:

- `storage/`
- `storage/logs/`
- `storage/sessions/`
- `storage/backups/`
- `storage/exports/`
- `public/uploads/`

Generated files currently include:

- animal photo uploads in `public/uploads/animals/`
- adoption documents in `public/uploads/adoptions/documents/`
- QR SVG files in `public/uploads/qrcodes/`
- report exports in `storage/exports/reports/`
- compressed SQL backups in `storage/backups/`

## Security Model

- Passwords are hashed with bcrypt using cost 12.
- Authenticated browser and API requests share the same session-backed auth state.
- Session cookies are configured as `HttpOnly` and `SameSite=Strict`.
- Secure cookies are enabled automatically when the request is HTTPS or a trusted proxy reports HTTPS.
- CSRF validation is applied to state-changing routes that use browser sessions.
- Sensitive endpoints are rate limited via `rate_limit_attempts`.
- Authorization is enforced by role and permission middleware aliases defined in `config/app.php`.

## Release Checklist

- Set a real HTTPS `APP_URL`.
- Set a unique `APP_KEY`.
- Configure `TRUSTED_PROXIES` if TLS terminates upstream.
- Keep `APP_DEBUG=false` in production.
- Rotate the seeded admin password `ChangeMe@2025`.
- Ensure mail settings are complete if password-reset email must deliver outside QA.
- Confirm readiness checks in `/settings` return no `fail` entries.
- Rehearse backup creation and restore against a cloned database, not the live database.
- Verify that `storage/` and `public/uploads/` are writable by the PHP runtime user.

## Docs Map

- `README.md`: current runtime overview and setup
- `ARCHITECTURE.md`: request flow, directory map, and module boundaries
- `API_ROUTES.md`: current web and API route inventory
- `IMPLEMENTATION_GUIDE.md`: implemented runtime behavior and deployment notes
- `VALIDATION_RULES.md`: validator capabilities and endpoint-specific rule highlights
- `PAGE_LAYOUTS.md`: implemented page structure and screen responsibilities
- `PRD_Catarman_Dog_Pound.md`: product and scope document aligned to the current build
- `system_summary.md`: concise system inventory and counts
- `llm_context.md`: AI-facing implementation constraints and safe assumptions

## Notes on Historical Files

The chapter and manuscript Markdown files remain part of the capstone documentation set. They have been aligned to the current application where the content describes implemented system behavior, but their academic references and manuscript structure remain separate from the runtime documentation above.
