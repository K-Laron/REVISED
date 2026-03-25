# Architecture & Codebase Map
## Catarman Dog Pound & Animal Shelter Management System

### **1. High-Level Overview**
- **Language**: PHP 8.2
- **Architecture Pattern**: Custom MVC (Model-View-Controller) Framework (Vanilla PHP, no Laravel/CodeIgniter)
- **Dependency Management**: Composer with PSR-4 autoloading
- **Database**: MySQL 8.0+ (PDO Singleton with prepared statements)
- **Authentication**: JWT-based (JSON Web Tokens) with Role-Based Access Control (RBAC)

### **2. Directory Structure**

#### **Core Architecture (`src/`)**
Contains the backend logic, following standard PSR-4 namespaces.
- `src/Core/`: The foundation of the custom framework (Router, Request, Response, Database, Session, View Renderer, Logger).
- `src/Controllers/`: Request handlers for processing incoming HTTP requests across all domains (Animals, Adoptions, Medical, Billing, etc.).
- `src/Models/`: Database entity representations (ActiveRecord or DataMapper patterns).
- `src/Services/`: Business logic and workflows (e.g., `AdoptionService`, `MedicalService`). Keeps controllers thin.
- `src/Helpers/`: Utility classes providing pure functions (Validation, Sanitization, ID Generation).
- `src/Middleware/`: HTTP middleware for filtering requests (Auth checks, CORS, Rate Limiting).
- `src/Support/`: Enums, Traits, and general support abstractions.

#### **Routing & Entry points**
- `public/index.php`: The Front Controller and single entry point for all web traffic. Includes bootstrapping.
- `routes/web.php`: Defines routes that return HTML views (UI pages).
- `routes/api.php`: Defines RESTful JSON API endpoints for dynamic frontend calls.

#### **Views (`views/`)**
PHP template files organized by feature domains:
- `adoptions/`, `animals/`, `billing/`, `dashboard/`, `inventory/`, `medical/`, `reports/`, `search/`, `users/`
- `layouts/` & `partials/`: Reusable UI components.

#### **Configuration & Database**
- `config/`: Configuration arrays for the application (`app.php`, `database.php`, `auth.php`, `cors.php`, `mail.php`). These are typically populated by `.env` variables.
- `database/`: Database schema definitions, migrations, and seeders.
  - `database_schema.sql` (Schema definition)
  - `seeders.sql` (Initial data)

#### **Storage & Public Assets**
- `storage/`: Writable directory for application runtime data (logs, file cache, generated PDFs, sessions, backups).
- `public/assets/` & `public/uploads/`: Publicly accessible static files (CSS, JS, images, uploaded animal photos).

#### **Testing & Tooling**
- `tools/selenium-py/`: Python-based automated UI testing scripts using Selenium and Playwright.
- `output/`: Caching and output directories for the testing tools.

### **3. Application Domains (Modules)**
The application is partitioned into several key operational modules:
1. **Animal Intake & Profiling** (QR-coded IDs, photo uploads).
2. **Medical EMR** (Health records, vaccination tracking, treatments).
3. **Capacity/Kennel Management** (Real-time tracking of physical spaces).
4. **Adoptions** (Multi-step workflows: request, interview, seminar, billing).
5. **Billing & Invoices** (PDF generation, fee tracking).
6. **Inventory** (Supplies, food, medicine tracking with low-stock alerts).
7. **Reporting & Dashboard** (Executive views, PDF/CSV exports).

### **4. Security Mechanisms**
- **Authentication**: Stateless JWT mechanism, configured via `config/auth.php` and handled by Middleware.
- **SQL Injection Prevention**: Forced PDO prepared statements in `src/Core/Database.php`.
- **Environment config**: `.env` file prevents hardcoded secrets in the repository.
