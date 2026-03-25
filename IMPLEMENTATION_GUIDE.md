# Implementation Guide — Phase-by-Phase Build Order

> **AI INSTRUCTION:** Follow phases sequentially. Do NOT skip ahead. Each phase has a **verification checkpoint** — confirm it passes before moving to the next phase. Each file listed has a reference to the doc where its spec lives.

---

## Phase 0: Environment Setup
**Goal:** Local dev environment running and verified.

### Steps
```bash
# 1. Create project root
mkdir catarman-shelter && cd catarman-shelter

# 2. Initialize Composer
composer init --name="catarman/animal-shelter" --type=project --no-interaction

# 3. Install production dependencies
composer require vlucas/phpdotenv:^5.6 chillerlan/php-qrcode:^5.0 tecnickcom/tcpdf:^6.7 phpmailer/phpmailer:^6.9 intervention/image:^3.0 monolog/monolog:^3.5

# 4. Install dev dependencies
composer require --dev phpunit/phpunit:^10.5 fakerphp/faker:^1.23

# 5. Configure PSR-4 autoloading (already in composer.json from PRD Section 8)
composer dump-autoload

# 6. Create MySQL database
mysql -u root -p -e "CREATE DATABASE catarman_shelter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Run schema migration
mysql -u root -p catarman_shelter < database_schema.sql

# 8. Run seeders
mysql -u root -p catarman_shelter < seeders.sql

# 9. Create .env from template (see Phase 1)

# 10. Start dev server
php -S localhost:8000 -t public
```

### ✅ Checkpoint
- [ ] `php -v` returns 8.2+
- [ ] `mysql --version` returns 8.0+
- [ ] `composer install` completes without errors
- [ ] Database has 38 tables (run: `SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = 'catarman_shelter';`)
- [ ] Seeder data present (run: `SELECT COUNT(*) FROM roles;` → 6)
- [ ] PHP dev server starts at localhost:8000

---

## Phase 1: Project Skeleton & Core Classes
**Goal:** Folder structure, .env, autoloading, and core framework classes.
**Reference:** PRD Section 11 (Folder Structure), Section 8 (Architecture)

### Files to Create

| File | Purpose |
|------|---------|
| `.env.example` | Environment variable template |
| `.env` | Actual environment config (gitignored) |
| `.htaccess` | Apache rewrite rules |
| `public/index.php` | Single entry point — see `API_ROUTES.md` Router Implementation |
| `public/.htaccess` | Rewrite all requests to index.php |
| `config/app.php` | App bootstrap, load .env, set error reporting |
| `config/database.php` | Return DB config array from env vars |
| `config/cors.php` | CORS settings |
| `config/mail.php` | SMTP settings from env vars |
| `src/Core/Router.php` | URL router with method matching and middleware pipeline |
| `src/Core/Request.php` | HTTP request wrapper (body, query, files, headers) |
| `src/Core/Response.php` | JSON and HTML response builder (see `API_ROUTES.md` response format) |
| `src/Core/Database.php` | PDO singleton, prepared statements, transaction support |
| `src/Core/Session.php` | Session management with secure cookie config |
| `src/Core/View.php` | PHP template renderer with layout inheritance |
| `src/Core/Logger.php` | JSON structured logger using Monolog |
| `src/Helpers/Validator.php` | Input validation engine — see `VALIDATION_RULES.md` rule reference |
| `src/Helpers/Sanitizer.php` | Input sanitization — see `VALIDATION_RULES.md` sanitization section |
| `src/Helpers/IdGenerator.php` | Formatted ID generator using `id_sequences` table |
| `routes/web.php` | Web (HTML) routes — see `API_ROUTES.md` |
| `routes/api.php` | API (JSON) routes — see `API_ROUTES.md` |

### .env.example Template
```env
APP_NAME="Catarman Animal Shelter"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_KEY=             # Generate: php -r "echo bin2hex(random_bytes(32));"

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=catarman_shelter
DB_USERNAME=root
DB_PASSWORD=

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=noreply@catarmanshelter.gov.ph
MAIL_FROM_NAME="Catarman Animal Shelter"

LOG_LEVEL=debug
LOG_PATH=storage/logs

UPLOAD_MAX_SIZE=5242880
UPLOAD_PATH=storage/uploads
```

### ✅ Checkpoint
- [ ] Visiting `localhost:8000` returns a response (even if 404)
- [ ] `Database::connect()` connects without errors
- [ ] `Router` can match a test route and call a closure
- [ ] `Validator` passes/fails on a test rule set
- [ ] `IdGenerator::next('animal_id')` returns `A-2025-0001`
- [ ] `Logger` writes to `storage/logs/app-YYYY-MM-DD.log`

---

## Phase 2: Middleware & Authentication
**Goal:** Auth system with login, server-side sessions, RBAC, and rate limiting.
**Reference:** PRD Section 4 (RBAC), Section 6 (Security), `API_ROUTES.md` Auth routes

### Files to Create

| File | Purpose |
|------|---------|
| `src/Middleware/AuthMiddleware.php` | Verify active session, reject 401 |
| `src/Middleware/GuestMiddleware.php` | Redirect authenticated users away |
| `src/Middleware/RoleMiddleware.php` | Check `role_id` against required role |
| `src/Middleware/PermissionMiddleware.php` | Check `role_permissions` table for specific perm |
| `src/Middleware/RateLimitMiddleware.php` | IP-based using `rate_limit_attempts` table |
| `src/Middleware/CorsMiddleware.php` | Set CORS headers for API routes |
| `src/Middleware/CsrfMiddleware.php` | CSRF token generation and validation |
| `src/Models/User.php` | User model: find, create, authenticate, soft delete |
| `src/Models/Role.php` | Role model with permissions relationship |
| `src/Models/Permission.php` | Permission model |
| `src/Services/AuthService.php` | Login, logout, password reset, and active-session validation |
| `src/Controllers/AuthController.php` | Auth routes handler — see `API_ROUTES.md` Section 1 |
| `views/auth/login.php` | Login page template |
| `views/auth/forgot-password.php` | Forgot password template |
| `views/auth/reset-password.php` | Reset password template |

### Middleware Execution Order
```
Request → RateLimit → CORS → CSRF → Auth → Role → Permission → Controller
```

### ✅ Checkpoint
- [ ] Login with `admin@catarmanshelter.gov.ph` / `ChangeMe@2025` succeeds
- [ ] Session is created and persists across requests
- [ ] Accessing `/dashboard` without login redirects to `/login`
- [ ] Invalid credentials return `401 UNAUTHORIZED`
- [ ] Rate limiter blocks after 5 failed logins
- [ ] Logout destroys session
- [ ] CSRF token validation works on form submissions
- [ ] Middleware chain executes in correct order

---

## Phase 3: Layout Shell & Design System
**Goal:** Base HTML layout, CSS design tokens, theme toggle, toast system.
**Reference:** PRD Section 7 (Design System), `PAGE_LAYOUTS.md` Global Layout Shell

### Files to Create

| File | Purpose |
|------|---------|
| `public/assets/css/variables.css` | All CSS custom properties — copy from PRD 7.1 (both `:root` and `[data-theme='dark']`) |
| `public/assets/css/reset.css` | CSS reset / normalize |
| `public/assets/css/base.css` | Typography, body, global styles from PRD 7.2 |
| `public/assets/css/components.css` | Buttons, Cards, Inputs, Badges — PRD 7.3 |
| `public/assets/css/toast.css` | Toast notification styles — PRD 7.4 |
| `public/assets/css/layout.css` | Sidebar, topbar, main content grid — `PAGE_LAYOUTS.md` Global Shell |
| `public/assets/css/responsive.css` | Mobile breakpoints — PRD Section 9 |
| `public/assets/js/theme.js` | Dark/light toggle + OS detection — PRD 7.6 |
| `public/assets/js/toast.js` | Toast notification JS API — PRD 7.4 JS section |
| `public/assets/js/app.js` | Global utilities: sidebar toggle, dropdowns, modals |
| `views/layouts/app.php` | Main layout: topbar + sidebar + content slot |
| `views/layouts/public.php` | Public layout (adopter pages, no sidebar) |
| `views/partials/header.php` | Topbar with search, notifications, theme toggle, user menu |
| `views/partials/sidebar.php` | Navigation sidebar with module links and icons |
| `views/partials/footer.php` | Footer bar |

### ✅ Checkpoint
- [ ] Layout renders with sidebar + topbar + content area
- [ ] Dark/light toggle works, persists on reload
- [ ] No theme flash on page load (inline `<head>` script works)
- [ ] `toast.success('Title', 'Description')` shows toast notification
- [ ] Toast auto-dismisses after configured duration
- [ ] Multiple toasts stack correctly (max 3 visible)
- [ ] All components (buttons, cards, inputs) match design tokens
- [ ] Responsive: sidebar collapses at <768px
- [ ] Dark mode: buttons glow on hover, inputs glow on focus, cards brighten borders

---

## Phase 4: Dashboard
**Goal:** Shelter head dashboard with stats, charts, and activity feed.
**Reference:** `PAGE_LAYOUTS.md` Section 1, `API_ROUTES.md` Section 2

### Files to Create

| File | Purpose |
|------|---------|
| `src/Controllers/DashboardController.php` | Dashboard page + stats API endpoints |
| `src/Services/DashboardService.php` | Aggregate queries for stats, charts, activity |
| `views/dashboard/index.php` | Dashboard template with stat cards, chart containers, activity feed |
| `public/assets/js/dashboard.js` | Chart.js initialization, fetch stats API, render charts |
| `public/assets/css/dashboard.css` | Dashboard-specific layout (grid for stat cards + charts) |

### ✅ Checkpoint
- [ ] 4 stat cards render with real or zero counts
- [ ] Charts render (line, donut, bar) with Chart.js
- [ ] Charts respect theme colors (re-render on dark/light switch)
- [ ] Activity feed shows recent audit entries
- [ ] Quick action buttons navigate to correct pages

---

## Phase 5: Animal Module (Core CRUD + QR)
**Goal:** Animal intake, list, detail, edit, status change, QR generation/scanning.
**Reference:** PRD 5.4, `API_ROUTES.md` Section 3, `PAGE_LAYOUTS.md` Sections 2-4, `VALIDATION_RULES.md` Section 2

### Files to Create

| File | Purpose |
|------|---------|
| `src/Models/Animal.php` | Animal model with soft delete, status transitions, photo relationship |
| `src/Models/Breed.php` | Breed model (dropdown data) |
| `src/Models/AnimalPhoto.php` | Photo model with file path handling |
| `src/Models/AnimalQrCode.php` | QR code model |
| `src/Services/AnimalService.php` | CRUD, status transitions, search/filter, pagination |
| `src/Services/QrCodeService.php` | Generate QR (chillerlan/php-qrcode), lookup by scan |
| `src/Controllers/AnimalController.php` | All animal routes from API_ROUTES.md |
| `src/Controllers/QrCodeController.php` | QR generate, download, scan routes |
| `views/animals/index.php` | List page with filters + data table |
| `views/animals/create.php` | Intake form (see layout spec) |
| `views/animals/show.php` | Detail page with tabs: Timeline, Medical, Kennel History |
| `views/animals/edit.php` | Edit form (pre-populated) |
| `public/assets/js/animals.js` | List filtering, photo upload preview, QR scanner modal |
| `public/assets/css/animals.css` | Photo gallery, timeline, table styles |

### ✅ Checkpoint
- [ ] List page shows paginated animals with filters working
- [ ] Create form validates and saves animal with photo upload
- [ ] QR code auto-generates on animal creation
- [ ] QR scan (camera) resolves to animal detail page
- [ ] Status changes update status + timestamp + create audit log
- [ ] Detail page tabs render (even if Medical/Kennel empty)
- [ ] Soft delete and restore work

---

## Phase 6: Kennel Module
**Goal:** Kennel grid view, assignment, maintenance logging.
**Reference:** PRD 5.6, `API_ROUTES.md` Section 4, `PAGE_LAYOUTS.md` Section 5

### Files to Create

| File | Purpose |
|------|---------|
| `src/Models/Kennel.php` | Kennel model with occupancy status |
| `src/Models/KennelAssignment.php` | Assignment model (assign/release) |
| `src/Models/KennelMaintenanceLog.php` | Maintenance log model |
| `src/Services/KennelService.php` | Assignment logic, capacity checks, stats |
| `src/Controllers/KennelController.php` | All kennel routes |
| `views/kennels/index.php` | Grid map + list view toggle |
| `views/kennels/detail-panel.php` | Slide-in panel for kennel details |
| `public/assets/js/kennels.js` | Grid rendering, slide-in panel, assignment modal |
| `public/assets/css/kennels.css` | Grid cells, status colors, slide-in animation |

### ✅ Checkpoint
- [ ] Grid view shows all kennels with correct status colors
- [ ] Clicking a kennel opens detail slide-in panel
- [ ] Assign animal to kennel → kennel status becomes Occupied
- [ ] Release animal → kennel status returns to Available
- [ ] Maintenance log creates entry and sets kennel to Maintenance
- [ ] Stats bar shows correct counts

---

## Phase 7: Medical Records Module
**Goal:** Dynamic medical forms by procedure type, records linked to animals.
**Reference:** PRD 5.5, `API_ROUTES.md` Section 5, `PAGE_LAYOUTS.md` Section 6, `VALIDATION_RULES.md` Section 4

### Files to Create

| File | Purpose |
|------|---------|
| `src/Models/MedicalRecord.php` | Base medical record model |
| `src/Models/VaccinationRecord.php` | Vaccination sub-type model |
| `src/Models/SurgeryRecord.php` | Surgery sub-type model |
| `src/Models/ExaminationRecord.php` | Examination sub-type model |
| `src/Models/TreatmentRecord.php` | Treatment sub-type model |
| `src/Models/DewormingRecord.php` | Deworming sub-type model |
| `src/Models/EuthanasiaRecord.php` | Euthanasia sub-type model |
| `src/Services/MedicalService.php` | Create records (base + sub-type), due dates, history |
| `src/Controllers/MedicalController.php` | All medical routes |
| `views/medical/index.php` | Medical records list |
| `views/medical/create.php` | Dynamic form with procedure type selector |
| `views/medical/show.php` | Record detail view |
| `views/medical/partials/form-vaccination.php` | Vaccination fields partial |
| `views/medical/partials/form-surgery.php` | Surgery fields partial |
| `views/medical/partials/form-examination.php` | Examination fields partial |
| `views/medical/partials/form-treatment.php` | Treatment fields partial |
| `views/medical/partials/form-deworming.php` | Deworming fields partial |
| `views/medical/partials/form-euthanasia.php` | Euthanasia fields partial |
| `public/assets/js/medical.js` | Dynamic form switching, due date calculations |

### ✅ Checkpoint
- [ ] Selecting procedure type dynamically loads the correct form fields
- [ ] Record creates entry in BOTH `medical_records` and the sub-type table
- [ ] Vaccination record shows next due date
- [ ] Medical tab on animal detail page lists all records
- [ ] Treatment with inventory_item_id deducts stock (integration with Phase 9)

---

## Phase 8: Adoption Module
**Goal:** Full adoption pipeline: apply → interview → seminar → payment → complete.
**Reference:** PRD 5.7, `API_ROUTES.md` Section 6, `PAGE_LAYOUTS.md` Section 7

### Files to Create

| File | Purpose |
|------|---------|
| `src/Models/AdoptionApplication.php` | Application model with status transitions |
| `src/Models/AdoptionInterview.php` | Interview model |
| `src/Models/AdoptionSeminar.php` | Seminar model |
| `src/Models/SeminarAttendee.php` | Attendee model |
| `src/Models/AdoptionCompletion.php` | Completion model |
| `src/Services/AdoptionService.php` | Pipeline logic, stage transitions, notifications |
| `src/Controllers/AdoptionController.php` | Admin adoption management routes |
| `src/Controllers/AdopterPortalController.php` | Public-facing adopter routes |
| `views/adoptions/index.php` | Kanban pipeline board |
| `views/adoptions/show.php` | Application detail with tabs |
| `views/adoptions/seminars.php` | Seminar management page |
| `views/portal/landing.php` | Public adopter landing page — `PAGE_LAYOUTS.md` Section 12 |
| `views/portal/animals.php` | Public animal browsing page |
| `views/portal/animal-detail.php` | Public animal detail page |
| `views/portal/apply.php` | Application form |
| `views/portal/my-applications.php` | Adopter's application status tracker |
| `views/portal/register.php` | Adopter registration |
| `public/assets/js/adoptions.js` | Kanban drag (optional), status buttons |
| `public/assets/js/portal.js` | Public page interactions, form validation |
| `public/assets/css/portal.css` | Landing page hero, animal cards, public nav |
| `public/assets/css/adoptions.css` | Kanban board styles |

### ✅ Checkpoint
- [ ] Public landing page renders with hero + featured animals
- [ ] Adopter can register, login, and browse available animals
- [ ] Application form validates and creates record with status `pending_review`
- [ ] Staff can move applications through pipeline stages
- [ ] Interview scheduling creates notification for adopter
- [ ] Seminar creation + attendee registration works
- [ ] Adoption completion changes animal status to `Adopted`
- [ ] Adoption certificate PDF generates

---

## Phase 9: Billing Module
**Goal:** Invoice creation, line items, payments, fee schedule, PDF generation.
**Reference:** PRD 5.8, `API_ROUTES.md` Section 7, `PAGE_LAYOUTS.md` Section 8, `VALIDATION_RULES.md` Section 6

### Files to Create

| File | Purpose |
|------|---------|
| `src/Models/Invoice.php` | Invoice with computed balance_due |
| `src/Models/InvoiceLineItem.php` | Line item with computed total_price |
| `src/Models/Payment.php` | Payment model |
| `src/Models/FeeSchedule.php` | Fee schedule model |
| `src/Services/BillingService.php` | Invoice creation, payment processing, PDF generation |
| `src/Services/PdfService.php` | TCPDF wrapper for invoice/receipt/certificate generation |
| `src/Controllers/BillingController.php` | All billing routes |
| `views/billing/index.php` | Billing dashboard with tabs |
| `views/billing/create-invoice.php` | Invoice creation form with dynamic line items |
| `views/billing/show-invoice.php` | Invoice detail view |
| `public/assets/js/billing.js` | Dynamic line items, payment modal, auto-calculations |

### ✅ Checkpoint
- [ ] Invoice creates with auto-generated number (INV-YYYY-NNNN)
- [ ] Line items calculate total from quantity × unit_price
- [ ] Invoice total sums line items
- [ ] Payment records and updates `amount_paid` + `payment_status`
- [ ] Partial payment shows correct `balance_due`
- [ ] Invoice PDF downloads with correct data
- [ ] Fee schedule CRUD works
- [ ] Void invoice sets status and records reason

---

## Phase 10: Inventory Module
**Goal:** Stock management, transactions, low-stock alerts, expiry tracking.
**Reference:** PRD 5.9, `API_ROUTES.md` Section 8, `PAGE_LAYOUTS.md` Section 9, `VALIDATION_RULES.md` Section 7

### Files to Create

| File | Purpose |
|------|---------|
| `src/Models/InventoryItem.php` | Inventory item with stock level tracking |
| `src/Models/InventoryCategory.php` | Category model |
| `src/Models/StockTransaction.php` | Transaction model |
| `src/Services/InventoryService.php` | Stock in/out, alerts, expiry checks |
| `src/Controllers/InventoryController.php` | All inventory routes |
| `views/inventory/index.php` | Inventory list with alerts bar |
| `views/inventory/show.php` | Item detail with transaction history |
| `public/assets/js/inventory.js` | Quick stock modals, alert dismissal |

### ✅ Checkpoint
- [ ] Stock-in increases `quantity_on_hand` and creates transaction
- [ ] Stock-out decreases quantity and prevents going below 0
- [ ] Count correction (adjust) records difference
- [ ] Low stock alert shows items at or below reorder level
- [ ] Expiring items alert shows items within 30 days of expiry
- [ ] Transaction history shows full audit trail per item

---

## Phase 11: User Management & Reports
**Goal:** Admin user CRUD, report generation, PDF/CSV export.
**Reference:** PRD 5.10-5.11, `API_ROUTES.md` Sections 9-10, `PAGE_LAYOUTS.md` Sections 10-11

### Files to Create

| File | Purpose |
|------|---------|
| `src/Models/AuditLog.php` | Audit log model |
| `src/Models/Notification.php` | Notification model |
| `src/Models/ReportTemplate.php` | Saved report config model |
| `src/Services/UserService.php` | User CRUD, role assignment, soft delete |
| `src/Services/ReportService.php` | Report data aggregation, template rendering |
| `src/Services/ExportService.php` | CSV and PDF export generation |
| `src/Services/NotificationService.php` | Create, read, mark-read notifications |
| `src/Controllers/UserController.php` | User management routes |
| `src/Controllers/RoleController.php` | Role + permission management |
| `src/Controllers/ReportController.php` | Report generation routes |
| `src/Controllers/NotificationController.php` | Notification API routes |
| `views/users/index.php` | User list with filters |
| `views/users/create.php` | Create user form |
| `views/users/show.php` | User detail page |
| `views/reports/index.php` | Report center with type selector |
| `views/reports/viewer.php` | Report preview with chart + table |
| `public/assets/js/reports.js` | Report builder, chart preview, export triggers |
| `public/assets/js/notifications.js` | Notification dropdown, badge count polling |

### ✅ Checkpoint
- [ ] Admin can create, edit, deactivate, and soft-delete users
- [ ] Role change updates user's permissions
- [ ] Reports generate data grouped by day/week/month/quarter/year
- [ ] CSV export downloads valid CSV with correct data
- [ ] PDF export generates formatted report
- [ ] Animal dossier PDF includes all records for one animal
- [ ] Notification bell shows unread count
- [ ] Mark-as-read updates badge count

---

## Phase 12: System & Production Hardening
**Goal:** Backup system, audit trail viewer, logging, error pages, security polish.
**Reference:** PRD Section 6 (Non-Functional), Section 12 (Deployment)

### Files to Create

| File | Purpose |
|------|---------|
| `src/Models/SystemBackup.php` | Backup record model |
| `src/Services/BackupService.php` | mysqldump trigger, file management, restore |
| `src/Controllers/SystemController.php` | Health check, backup, audit log routes |
| `views/errors/404.php` | Clean 404 page |
| `views/errors/403.php` | Clean 403 (forbidden) page |
| `views/errors/500.php` | Clean 500 error page |
| `views/errors/maintenance.php` | Maintenance mode page |
| `src/Core/ExceptionHandler.php` | Global error handler: log errors, show clean fallback |

### Security Checklist
- [ ] All inputs sanitized via `Sanitizer::clean()`
- [ ] All DB queries use prepared statements (no string concatenation)
- [ ] CSRF tokens on every form
- [ ] Rate limiting on login, password reset, registration
- [ ] Password hashed with `password_hash(PASSWORD_BCRYPT)`
- [ ] Sessions use `httponly`, `secure`, `samesite=strict` cookies
- [ ] File uploads validated: type, size, renamed with UUID
- [ ] Error pages show clean messages, never stack traces

### ✅ Checkpoint
- [ ] `/api/system/health` returns 200 with uptime and DB status
- [ ] Backup creates `.sql.gz` file and records in `system_backups` table
- [ ] 404 page shows clean minimalist error (not PHP trace)
- [ ] 500 errors log to file but show clean page to user
- [ ] Audit trail viewer (super_admin only) shows paginated logs
- [ ] All security checklist items confirmed

---

## Phase 13: Integration Testing & Final QA
**Goal:** End-to-end verification of all modules working together.

### Cross-Module Integration Tests

| Test | Steps | Expected |
|------|-------|----------|
| Full adoption flow | Register adopter → Apply → Schedule interview → Complete interview → Schedule seminar → Mark attendance → Create invoice → Record payment → Complete adoption | Animal status = Adopted, invoice Paid, certificate generated |
| Medical with inventory | Create treatment for animal → Select inventory item → Save | Stock decreases, transaction logged |
| QR to animal dossier | Generate QR → Scan QR → View animal → Export dossier PDF | PDF contains all animal data + medical + kennel history |
| Dashboard accuracy | Create 3 animals, 1 adoption, 2 medical records → View dashboard | Stat cards show correct counts, charts reflect new data |
| Theme persistence | Set dark mode → Reload → Navigate pages | Dark mode persists everywhere including charts |
| Mobile responsiveness | Resize to 375px → Navigate all pages | Sidebar collapsed, tables become cards, forms full-width |

### ✅ Final Checkpoint
- [ ] All 6 integration tests pass
- [ ] All forms validate correctly (test with empty + invalid data)
- [ ] All toast notifications appear correctly (success, error, warning)
- [ ] PDF exports open without corruption
- [ ] No console errors in browser dev tools
- [ ] All 120+ API endpoints return correct response format
- [ ] Dark mode looks correct on every page

---

## Quick Reference: Phase Dependencies

```
Phase 0 (Setup)
  └→ Phase 1 (Core)
       └→ Phase 2 (Auth)
            └→ Phase 3 (Design System)
                 ├→ Phase 4 (Dashboard) ←───── needs charts data from all modules
                 ├→ Phase 5 (Animals) ←────── core entity, everything depends on this
                 │    └→ Phase 6 (Kennels) ← needs animals
                 │    └→ Phase 7 (Medical) ← needs animals + inventory (Phase 10)
                 │    └→ Phase 8 (Adoptions) ← needs animals + billing (Phase 9)
                 ├→ Phase 9 (Billing) ←────── can be built in parallel with 5
                 └→ Phase 10 (Inventory) ←── can be built in parallel with 5
                      └→ Phase 11 (Users & Reports) ← needs all data modules
                           └→ Phase 12 (Hardening)
                                └→ Phase 13 (Integration Testing)
```

> **Note for AI:** Phases 5, 9, and 10 can be built in parallel since they are independent. However, Phase 7 (Medical) depends on Phase 10 (Inventory) for treatment stock deduction, and Phase 8 (Adoptions) depends on Phase 9 (Billing) for payment processing. Build 5→9→10 first, then 6→7→8.

---

## Production Runbook

Use this after the implementation phases are complete and before go-live.

### Environment Hardening
- Set `APP_ENV=production`
- Set `APP_DEBUG=false`
- Set `APP_URL` to the public HTTPS URL
- Set a unique random `APP_KEY`
- Set `TRUSTED_PROXIES` when running behind a reverse proxy or TLS terminator
- Keep `SESSION_LIFETIME` at `60` minutes or less for staff/admin access

### Security & Access
- Rotate the seeded admin password `ChangeMe@2025`
- Confirm `force_password_change` is enforced for any temporary or seeded credentials
- Verify mail mode:
  - local/staging may use `log_only`
  - production recovery/notifications should use `smtp` or a documented manual process

### Storage & Operations
- Ensure PHP can write to:
  - `storage/`
  - `storage/logs/`
  - `storage/sessions/`
  - `storage/backups/`
- Rehearse backup creation and restore against a cloned database
- Do not test restore against the live production database first

### Web-Server / Proxy Setup
- Serve `public/` as the web root
- Terminate HTTPS at the proxy or web server
- Forward `X-Forwarded-For` and `X-Forwarded-Proto` correctly if PHP is behind the proxy
- Confirm session cookies remain `Secure`, `HttpOnly`, and `SameSite=Strict`

### Final Smoke Pass
- Browser login reaches `/dashboard`
- Forced-password-change flow works when enabled
- `/settings` readiness has no `fail` items
- Public `/adopt` respects maintenance mode and portal enable/disable settings
- Backup creation succeeds
- Core write flows pass:
  - animals create/edit
  - medical create
  - inventory create/update
  - adoptions detail actions
  - reports export
