# API Route Definitions

> **AI IMPLEMENTATION INSTRUCTION:** Every route below maps to a controller method. Create routes in `routes/web.php` (page routes) and `routes/api.php` (JSON API routes). All API routes return JSON. All web routes return HTML views.

## Standard Response Format

> AI MUST use this exact JSON structure for ALL API responses.

### Success Response
```json
{
  "success": true,
  "data": { },
  "meta": {
    "page": 1,
    "per_page": 20,
    "total": 150,
    "total_pages": 8
  },
  "message": "Animals retrieved successfully"
}
```

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "The given data was invalid.",
    "details": {
      "email": ["Email is required", "Must be a valid email address"],
      "name": ["Name must be at least 2 characters"]
    }
  }
}
```

### Error Codes
| Code | HTTP Status | Usage |
|------|-------------|-------|
| `VALIDATION_ERROR` | 422 | Form/input validation failed |
| `NOT_FOUND` | 404 | Record doesn't exist or is soft-deleted |
| `UNAUTHORIZED` | 401 | Not logged in or token expired |
| `FORBIDDEN` | 403 | Logged in but lacks permission |
| `RATE_LIMITED` | 429 | Too many requests |
| `CONFLICT` | 409 | Duplicate entry or state conflict |
| `SERVER_ERROR` | 500 | Unexpected server error |

---

## Middleware Keys
| Key | Class | Purpose |
|-----|-------|---------|
| `auth` | `AuthMiddleware` | Requires valid session/JWT |
| `guest` | `GuestMiddleware` | Only non-authenticated users |
| `role:X` | `RoleMiddleware` | Requires role X (comma-separated for multiple) |
| `perm:X` | `PermissionMiddleware` | Requires permission X |
| `throttle:N` | `RateLimitMiddleware` | Max N requests per minute |
| `csrf` | `CsrfMiddleware` | CSRF token validation (web routes) |

---

## 1. Authentication Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/login` | `AuthController@showLogin` | `guest` | Login page |
| POST | `/api/auth/login` | `AuthController@login` | `guest, throttle:5` | Authenticate user |
| POST | `/api/auth/logout` | `AuthController@logout` | `auth` | Destroy session |
| GET | `/forgot-password` | `AuthController@showForgotPassword` | `guest` | Forgot password page |
| POST | `/api/auth/forgot-password` | `AuthController@forgotPassword` | `guest, throttle:3` | Send reset email |
| GET | `/reset-password/{token}` | `AuthController@showResetPassword` | `guest` | Reset password page |
| POST | `/api/auth/reset-password` | `AuthController@resetPassword` | `guest, throttle:3` | Process reset |
| GET | `/api/auth/me` | `AuthController@me` | `auth` | Get current user profile |
| PUT | `/api/auth/profile` | `AuthController@updateProfile` | `auth` | Update own profile |
| PUT | `/api/auth/change-password` | `AuthController@changePassword` | `auth` | Change own password |

---

## 2. Dashboard Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/dashboard` | `DashboardController@index` | `auth` | Dashboard page |
| GET | `/api/dashboard/stats` | `DashboardController@stats` | `auth` | Key metrics JSON |
| GET | `/api/dashboard/charts/intake` | `DashboardController@intakeChart` | `auth` | Intake trend data |
| GET | `/api/dashboard/charts/adoptions` | `DashboardController@adoptionChart` | `auth` | Adoption trend data |
| GET | `/api/dashboard/charts/occupancy` | `DashboardController@occupancyChart` | `auth` | Kennel occupancy data |
| GET | `/api/dashboard/charts/medical` | `DashboardController@medicalChart` | `auth` | Medical procedures data |
| GET | `/api/dashboard/activity` | `DashboardController@recentActivity` | `auth` | Recent activity feed |

---

## 3. Animal Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/animals` | `AnimalController@index` | `auth, perm:animals.read` | Animal list page |
| GET | `/animals/create` | `AnimalController@create` | `auth, perm:animals.create` | Intake form page |
| GET | `/animals/{id}` | `AnimalController@show` | `auth, perm:animals.read` | Animal detail page |
| GET | `/animals/{id}/edit` | `AnimalController@edit` | `auth, perm:animals.update` | Edit animal page |
| GET | `/api/animals` | `AnimalController@list` | `auth, perm:animals.read` | Paginated animal list |
| POST | `/api/animals` | `AnimalController@store` | `auth, perm:animals.create` | Create animal record |
| GET | `/api/animals/{id}` | `AnimalController@get` | `auth, perm:animals.read` | Single animal JSON |
| PUT | `/api/animals/{id}` | `AnimalController@update` | `auth, perm:animals.update` | Update animal |
| DELETE | `/api/animals/{id}` | `AnimalController@destroy` | `auth, perm:animals.delete` | Soft delete animal |
| POST | `/api/animals/{id}/restore` | `AnimalController@restore` | `auth, perm:animals.delete` | Restore soft-deleted |
| PUT | `/api/animals/{id}/status` | `AnimalController@updateStatus` | `auth, perm:animals.update` | Change animal status |
| POST | `/api/animals/{id}/photos` | `AnimalController@uploadPhoto` | `auth, perm:animals.update` | Upload photo |
| DELETE | `/api/animals/{id}/photos/{photoId}` | `AnimalController@deletePhoto` | `auth, perm:animals.update` | Remove photo |
| GET | `/api/animals/{id}/qr` | `QrCodeController@generate` | `auth, perm:animals.read` | Generate/get QR code |
| GET | `/api/animals/{id}/qr/download` | `QrCodeController@download` | `auth, perm:animals.read` | Download QR as PNG |
| GET | `/api/animals/scan/{qrData}` | `QrCodeController@scan` | `auth` | Lookup animal by QR data |
| GET | `/api/breeds` | `BreedController@list` | `auth` | Breed dropdown list |
| GET | `/api/animals/{id}/timeline` | `AnimalController@timeline` | `auth, perm:animals.read` | Full history timeline |

**Query Parameters for `GET /api/animals`:**
```
?page=1
&per_page=20
&search=Rex                    # Name or animal_id
&species=Dog                   # Dog, Cat, Other
&status=Available              # Available, Under Medical Care, etc.
&intake_type=Stray
&gender=Male
&size=Medium
&date_from=2025-01-01
&date_to=2025-12-31
&sort_by=intake_date           # intake_date, name, status, species
&sort_dir=desc                 # asc, desc
```

---

## 4. Kennel Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/kennels` | `KennelController@index` | `auth, perm:kennels.read` | Kennel map page |
| GET | `/api/kennels` | `KennelController@list` | `auth, perm:kennels.read` | All kennels with status |
| POST | `/api/kennels` | `KennelController@store` | `auth, perm:kennels.create` | Create kennel |
| PUT | `/api/kennels/{id}` | `KennelController@update` | `auth, perm:kennels.update` | Update kennel info |
| DELETE | `/api/kennels/{id}` | `KennelController@destroy` | `auth, perm:kennels.delete` | Soft delete kennel |
| POST | `/api/kennels/{id}/assign` | `KennelController@assignAnimal` | `auth, perm:kennels.update` | Assign animal to kennel |
| POST | `/api/kennels/{id}/release` | `KennelController@releaseAnimal` | `auth, perm:kennels.update` | Release animal from kennel |
| GET | `/api/kennels/{id}/history` | `KennelController@history` | `auth, perm:kennels.read` | Assignment history |
| GET | `/api/kennels/stats` | `KennelController@stats` | `auth, perm:kennels.read` | Occupancy statistics |
| POST | `/api/kennels/{id}/maintenance` | `KennelController@logMaintenance` | `auth, perm:kennels.update` | Log maintenance |
| GET | `/api/kennels/{id}/maintenance` | `KennelController@maintenanceHistory` | `auth, perm:kennels.read` | Maintenance history |

---

## 5. Medical Records Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/medical` | `MedicalController@index` | `auth, perm:medical.read` | Medical records list page |
| GET | `/medical/create/{animalId}` | `MedicalController@create` | `auth, perm:medical.create` | New medical record form |
| GET | `/medical/{id}` | `MedicalController@show` | `auth, perm:medical.read` | View medical record |
| GET | `/api/medical` | `MedicalController@list` | `auth, perm:medical.read` | Paginated medical records |
| GET | `/api/medical/animal/{animalId}` | `MedicalController@byAnimal` | `auth, perm:medical.read` | All records for animal |
| POST | `/api/medical/vaccination` | `MedicalController@storeVaccination` | `auth, perm:medical.create` | Create vaccination record |
| POST | `/api/medical/surgery` | `MedicalController@storeSurgery` | `auth, perm:medical.create` | Create surgery record |
| POST | `/api/medical/examination` | `MedicalController@storeExamination` | `auth, perm:medical.create` | Create examination record |
| POST | `/api/medical/treatment` | `MedicalController@storeTreatment` | `auth, perm:medical.create` | Create treatment record |
| POST | `/api/medical/deworming` | `MedicalController@storeDeworming` | `auth, perm:medical.create` | Create deworming record |
| POST | `/api/medical/euthanasia` | `MedicalController@storeEuthanasia` | `auth, perm:medical.create` | Create euthanasia record |
| PUT | `/api/medical/{id}` | `MedicalController@update` | `auth, perm:medical.update` | Update medical record |
| DELETE | `/api/medical/{id}` | `MedicalController@destroy` | `auth, perm:medical.delete` | Soft delete medical record |
| GET | `/api/medical/due-vaccinations` | `MedicalController@dueVaccinations` | `auth, perm:medical.read` | Upcoming vaccinations |
| GET | `/api/medical/due-dewormings` | `MedicalController@dueDewormings` | `auth, perm:medical.read` | Upcoming dewormings |
| GET | `/api/medical/form-config/{type}` | `MedicalController@formConfig` | `auth, perm:medical.read` | Dynamic form fields by type |

---

## 6. Adoption Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/adoptions` | `AdoptionController@index` | `auth, perm:adoptions.read` | Adoption pipeline (kanban) page |
| GET | `/adoptions/{id}` | `AdoptionController@show` | `auth, perm:adoptions.read` | Application detail page |
| GET | `/api/adoptions` | `AdoptionController@list` | `auth, perm:adoptions.read` | Paginated applications |
| GET | `/api/adoptions/{id}` | `AdoptionController@get` | `auth, perm:adoptions.read` | Single application JSON |
| PUT | `/api/adoptions/{id}/status` | `AdoptionController@updateStatus` | `auth, perm:adoptions.update` | Move to next stage |
| PUT | `/api/adoptions/{id}/reject` | `AdoptionController@reject` | `auth, perm:adoptions.update` | Reject application |
| POST | `/api/adoptions/{id}/interview` | `AdoptionController@scheduleInterview` | `auth, perm:adoptions.update` | Schedule interview |
| PUT | `/api/adoptions/interviews/{id}` | `AdoptionController@completeInterview` | `auth, perm:adoptions.update` | Complete interview with notes |
| POST | `/api/adoptions/seminars` | `AdoptionController@createSeminar` | `auth, perm:adoptions.create` | Create seminar event |
| GET | `/api/adoptions/seminars` | `AdoptionController@listSeminars` | `auth, perm:adoptions.read` | List seminars |
| POST | `/api/adoptions/seminars/{id}/attendees` | `AdoptionController@addAttendee` | `auth, perm:adoptions.update` | Register for seminar |
| PUT | `/api/adoptions/seminars/{id}/attendance` | `AdoptionController@markAttendance` | `auth, perm:adoptions.update` | Mark attendance |
| POST | `/api/adoptions/{id}/complete` | `AdoptionController@complete` | `auth, perm:adoptions.update` | Finalize adoption |
| GET | `/api/adoptions/{id}/certificate` | `AdoptionController@certificate` | `auth, perm:adoptions.read` | Download adoption certificate PDF |
| GET | `/api/adoptions/pipeline-stats` | `AdoptionController@pipelineStats` | `auth, perm:adoptions.read` | Pipeline stage counts |

### Adopter Portal Routes (Public-facing, requires adopter auth)
| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/adopt` | `AdopterPortalController@landing` | — | Public landing page |
| GET | `/adopt/animals` | `AdopterPortalController@availableAnimals` | — | Browse available animals |
| GET | `/adopt/animals/{id}` | `AdopterPortalController@animalDetail` | — | Animal detail public page |
| GET | `/adopt/apply` | `AdopterPortalController@showApply` | `auth, role:adopter` | Application form |
| POST | `/api/adopt/apply` | `AdopterPortalController@submitApplication` | `auth, role:adopter, throttle:3` | Submit adoption application |
| GET | `/api/adopt/my-applications` | `AdopterPortalController@myApplications` | `auth, role:adopter` | Adopter's own applications |
| GET | `/adopt/register` | `AdopterPortalController@showRegister` | `guest` | Adopter registration page |
| POST | `/api/adopt/register` | `AdopterPortalController@register` | `guest, throttle:5` | Create adopter account |

---

## 7. Billing Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/billing` | `BillingController@index` | `auth, perm:billing.read` | Billing dashboard page |
| GET | `/billing/invoices/create` | `BillingController@createInvoice` | `auth, perm:billing.create` | New invoice form |
| GET | `/billing/invoices/{id}` | `BillingController@showInvoice` | `auth, perm:billing.read` | Invoice detail page |
| GET | `/api/billing/invoices` | `BillingController@listInvoices` | `auth, perm:billing.read` | Paginated invoices |
| POST | `/api/billing/invoices` | `BillingController@storeInvoice` | `auth, perm:billing.create` | Create invoice |
| PUT | `/api/billing/invoices/{id}` | `BillingController@updateInvoice` | `auth, perm:billing.update` | Update invoice |
| POST | `/api/billing/invoices/{id}/void` | `BillingController@voidInvoice` | `auth, perm:billing.delete` | Void invoice |
| GET | `/api/billing/invoices/{id}/pdf` | `BillingController@invoicePdf` | `auth, perm:billing.read` | Download invoice PDF |
| POST | `/api/billing/invoices/{id}/payments` | `BillingController@recordPayment` | `auth, perm:billing.create` | Record payment |
| GET | `/api/billing/payments` | `BillingController@listPayments` | `auth, perm:billing.read` | Paginated payments |
| GET | `/api/billing/payments/{id}/receipt` | `BillingController@receiptPdf` | `auth, perm:billing.read` | Download receipt PDF |
| GET | `/api/billing/fee-schedule` | `BillingController@feeSchedule` | `auth, perm:billing.read` | Active fee list |
| POST | `/api/billing/fee-schedule` | `BillingController@storeFee` | `auth, perm:billing.create` | Create fee item |
| PUT | `/api/billing/fee-schedule/{id}` | `BillingController@updateFee` | `auth, perm:billing.update` | Update fee item |
| GET | `/api/billing/stats` | `BillingController@stats` | `auth, perm:billing.read` | Revenue stats |

---

## 8. Inventory Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/inventory` | `InventoryController@index` | `auth, perm:inventory.read` | Inventory list page |
| GET | `/api/inventory` | `InventoryController@list` | `auth, perm:inventory.read` | Paginated items |
| POST | `/api/inventory` | `InventoryController@store` | `auth, perm:inventory.create` | Create item |
| PUT | `/api/inventory/{id}` | `InventoryController@update` | `auth, perm:inventory.update` | Update item |
| DELETE | `/api/inventory/{id}` | `InventoryController@destroy` | `auth, perm:inventory.delete` | Soft delete item |
| POST | `/api/inventory/{id}/stock-in` | `InventoryController@stockIn` | `auth, perm:inventory.update` | Add stock |
| POST | `/api/inventory/{id}/stock-out` | `InventoryController@stockOut` | `auth, perm:inventory.update` | Remove stock |
| POST | `/api/inventory/{id}/adjust` | `InventoryController@adjust` | `auth, perm:inventory.update` | Count correction |
| GET | `/api/inventory/{id}/transactions` | `InventoryController@transactions` | `auth, perm:inventory.read` | Transaction history |
| GET | `/api/inventory/categories` | `InventoryController@categories` | `auth, perm:inventory.read` | Category list |
| POST | `/api/inventory/categories` | `InventoryController@storeCategory` | `auth, perm:inventory.create` | Create category |
| GET | `/api/inventory/alerts` | `InventoryController@alerts` | `auth, perm:inventory.read` | Low stock + expiring items |
| GET | `/api/inventory/stats` | `InventoryController@stats` | `auth, perm:inventory.read` | Inventory value stats |

---

## 9. User Management Routes (Admin)

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/users` | `UserController@index` | `auth, perm:users.read` | User list page |
| GET | `/users/create` | `UserController@create` | `auth, perm:users.create` | Create user form |
| GET | `/users/{id}` | `UserController@show` | `auth, perm:users.read` | User detail page |
| GET | `/api/users` | `UserController@list` | `auth, perm:users.read` | Paginated users |
| POST | `/api/users` | `UserController@store` | `auth, perm:users.create` | Create user |
| PUT | `/api/users/{id}` | `UserController@update` | `auth, perm:users.update` | Update user |
| DELETE | `/api/users/{id}` | `UserController@destroy` | `auth, perm:users.delete` | Soft delete user |
| POST | `/api/users/{id}/restore` | `UserController@restore` | `auth, perm:users.delete` | Restore user |
| PUT | `/api/users/{id}/role` | `UserController@changeRole` | `auth, perm:users.update` | Change user role |
| POST | `/api/users/{id}/reset-password` | `UserController@adminResetPassword` | `auth, perm:users.update` | Admin-trigger password reset |
| GET | `/api/users/{id}/sessions` | `UserController@sessions` | `auth, perm:users.read` | Active sessions |
| DELETE | `/api/users/{id}/sessions/{sessionId}` | `UserController@killSession` | `auth, perm:users.update` | Force logout session |
| GET | `/api/roles` | `RoleController@list` | `auth, perm:users.read` | List roles |
| GET | `/api/roles/{id}/permissions` | `RoleController@permissions` | `auth, perm:users.read` | Role's permissions |
| PUT | `/api/roles/{id}/permissions` | `RoleController@updatePermissions` | `auth, role:super_admin` | Update role permissions |

---

## 10. Reports & Export Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/reports` | `ReportController@index` | `auth, perm:reports.read` | Reports center page |
| GET | `/api/reports/intake` | `ReportController@intake` | `auth, perm:reports.read` | Intake summary data |
| GET | `/api/reports/medical` | `ReportController@medical` | `auth, perm:reports.read` | Medical summary data |
| GET | `/api/reports/adoptions` | `ReportController@adoptions` | `auth, perm:reports.read` | Adoption summary data |
| GET | `/api/reports/billing` | `ReportController@billing` | `auth, perm:reports.read` | Billing/revenue data |
| GET | `/api/reports/inventory` | `ReportController@inventory` | `auth, perm:reports.read` | Inventory summary data |
| GET | `/api/reports/census` | `ReportController@census` | `auth, perm:reports.read` | Current animal census |
| GET | `/api/reports/audit-trail` | `ReportController@auditTrail` | `auth, role:super_admin` | Audit log viewer |
| POST | `/api/reports/export` | `ReportController@export` | `auth, perm:reports.export` | Export report as PDF/CSV |
| GET | `/api/reports/animal-dossier/{animalId}` | `ReportController@animalDossier` | `auth, perm:reports.read` | Full animal dossier PDF |
| GET | `/api/reports/templates` | `ReportController@templates` | `auth, perm:reports.read` | Saved report templates |
| POST | `/api/reports/templates` | `ReportController@saveTemplate` | `auth, perm:reports.create` | Save report config |

**Common Report Query Parameters:**
```
?date_from=2025-01-01
&date_to=2025-12-31
&group_by=month        # day, week, month, quarter, year
&species=Dog
&status=Available
&format=json           # json (for charts), csv, pdf
```

---

## 11. Notification Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/api/notifications` | `NotificationController@list` | `auth` | Paginated notifications |
| GET | `/api/notifications/unread-count` | `NotificationController@unreadCount` | `auth` | Badge count |
| PUT | `/api/notifications/{id}/read` | `NotificationController@markRead` | `auth` | Mark single as read |
| PUT | `/api/notifications/read-all` | `NotificationController@markAllRead` | `auth` | Mark all as read |

---

## 12. System Routes

| Method | Endpoint | Controller | Middleware | Purpose |
|--------|----------|------------|------------|---------|
| GET | `/api/system/health` | `SystemController@health` | — | Health check (uptime, DB) |
| POST | `/api/system/backup` | `SystemController@createBackup` | `auth, role:super_admin` | Trigger database backup |
| GET | `/api/system/backups` | `SystemController@listBackups` | `auth, role:super_admin` | Backup history |
| POST | `/api/system/backups/{id}/restore` | `SystemController@restoreBackup` | `auth, role:super_admin` | Restore from backup |
| GET | `/api/system/audit-logs` | `SystemController@auditLogs` | `auth, role:super_admin` | Paginated audit logs |

---

## Router Implementation

> AI IMPLEMENTATION INSTRUCTION: Place this in `public/index.php`

```php
<?php
// public/index.php — Single entry point
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Bootstrap application
$app = require_once __DIR__ . '/../config/app.php';

// Load routes
$router = new \App\Core\Router();
require_once __DIR__ . '/../routes/web.php';
require_once __DIR__ . '/../routes/api.php';

// Dispatch request
$request = \App\Core\Request::capture();
$router->dispatch($request);
```

### Middleware Execution Order
```
1. RateLimitMiddleware     → Check rate limits
2. CorsMiddleware          → Set CORS headers (API routes only)
3. CsrfMiddleware          → Validate CSRF token (web routes only)
4. AuthMiddleware           → Verify session/JWT
5. RoleMiddleware           → Check user role
6. PermissionMiddleware     → Check specific permission
7. Controller Method        → Execute business logic
```
