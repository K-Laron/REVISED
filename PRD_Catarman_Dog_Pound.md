# Product Requirements Document (PRD)
# Catarman Dog Pound & Animal Shelter Management System

> **Version:** 1.0  
> **Date:** March 23, 2026  
> **Author:** Capstone Team  
> **Project Type:** Capstone Project — Web-Based Management System  
> **Status:** Draft
>
> **Implementation Note (March 24, 2026):** The running codebase uses server-side PHP sessions plus persisted `user_sessions` validation for authenticated browser and API access. This PRD reflects that current implementation state.

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Project Overview](#2-project-overview)
3. [Goals & Objectives](#3-goals--objectives)
4. [User Roles & Permissions (RBAC)](#4-user-roles--permissions-rbac)
5. [Functional Requirements](#5-functional-requirements)
   - 5.1 Landing Page (Public)
   - 5.2 Authentication & User Account Management
   - 5.3 Dashboard
   - 5.4 Animal Intake Module
   - 5.5 Medical Records Module
   - 5.6 Kennel Management Module
   - 5.7 Adoption Management Module
   - 5.8 Billing & Invoice Module
   - 5.9 Inventory Management Module
   - 5.10 User Management Module (Admin)
   - 5.11 Reports & Export Module
6. [Non-Functional / Production Requirements](#6-non-functional--production-requirements)
7. [Design System — Minimalist](#7-design-system--minimalist)
8. [System Architecture](#8-system-architecture)
9. [Mobile Responsive Layout](#9-mobile-responsive-layout)
10. [Database Design Considerations](#10-database-design-considerations)
11. [Production Folder Structure](#11-production-folder-structure)
12. [Deployment & Rollback Strategy](#12-deployment--rollback-strategy)
13. [Supplementary Documents (AI Build References)](#13-supplementary-documents-ai-build-references)
14. [Appendices](#14-appendices)

---

## 1. Executive Summary

The **Catarman Dog Pound & Animal Shelter Management System** is a full-stack web application designed to digitize and streamline all operational workflows of the Catarman municipal dog pound and animal shelter. The system covers the entire animal lifecycle — from intake and kennel assignment, through medical treatment and care, to adoption or other outcomes — while providing robust billing, inventory tracking, and administrative tooling.

Key differentiators include **automatic QR code generation and scanning** for rapid animal identification, **dynamic medical record forms** that adapt based on the type of procedure or treatment, a **multi-step adoption workflow** (request → interview → seminar → billing), and a **real-time executive dashboard** for shelter leadership.

The system is built with production-grade concerns: role-based access control (RBAC), input validation/sanitization, rate limiting, CORS policies, structured logging, and a blue-green deployment rollback strategy.

---

## 2. Project Overview

| Attribute              | Detail                                                        |
| ---------------------- | ------------------------------------------------------------- |
| **Project Name**       | Catarman Dog Pound & Animal Shelter Management System         |
| **Client/Stakeholder** | Municipal Government of Catarman / Dog Pound Administration   |
| **Platform**           | Web Application (responsive, mobile-friendly)                 |
| **Target Users**       | Shelter Staff, Veterinarians, Admin, Shelter Head, Adopters   |
| **Primary Goal**       | Digitize and automate shelter operations end-to-end           |

### Problem Statement

The Catarman Dog Pound currently relies on manual, paper-based processes for animal intake, medical records, adoption processing, billing, and inventory management. This leads to:

- **Data loss and inconsistency** from paper records
- **Slow adoption processing** due to manual, multi-step workflows
- **No real-time visibility** into shelter capacity, animal status, or financials
- **Inefficient inventory tracking**, leading to stock-outs of essential supplies
- **Difficulty generating reports** for government compliance and decision-making

### Proposed Solution

A centralized web application that provides:

- Digital animal intake with QR-coded identification tags
- Electronic medical records with procedure-specific dynamic forms
- Visual kennel management with real-time availability
- Structured multi-step adoption pipeline
- Automated billing and invoice generation
- Inventory management with low-stock alerts
- Comprehensive reporting and data export
- Executive dashboard for shelter leadership

---

## 3. Goals & Objectives

### Primary Goals

1. **Eliminate paper-based workflows** — 100% digital record-keeping for animals, medical procedures, adoptions, billing, and inventory.
2. **Accelerate adoption processing** — Reduce adoption cycle time through a structured, tracked pipeline.
3. **Enable data-driven decisions** — Provide the shelter head with a real-time dashboard and comprehensive reports.
4. **Ensure data integrity and security** — Implement RBAC, soft deletes, automated backups, input validation, and audit logging.

### Success Metrics

| Metric                          | Target                              |
| ------------------------------- | ----------------------------------- |
| Animal intake digitization      | 100% of new intakes via system      |
| Adoption pipeline tracking      | 100% of adoptions tracked end-to-end|
| Report generation time          | < 5 seconds for any standard report |
| System uptime                   | 99.5%+ availability                 |
| Data backup frequency           | Daily automated backups             |

---

## 4. User Roles & Permissions (RBAC)

The system implements **Role-Based Access Control** with the following roles:

| Role                | Description                                      | Key Permissions                                                                                     |
| ------------------- | ------------------------------------------------ | --------------------------------------------------------------------------------------------------- |
| **Super Admin**     | System administrator, full access                | All modules, user management, system config, data backup/restore, role management                   |
| **Shelter Head**    | Operational leader of the shelter                 | Dashboard (full), reports, adoption approval, all read access, staff oversight                       |
| **Veterinarian**    | Licensed vet staff                                | Medical records (full CRUD), animal intake (read), kennel view, inventory (medical supplies only)    |
| **Shelter Staff**   | Day-to-day operations personnel                   | Animal intake, kennel management, adoption processing, billing, inventory (general supplies)         |
| **Billing Clerk**   | Handles financial transactions                    | Billing (full CRUD), invoice generation, payment recording, financial reports                        |
| **Adopter**         | Public-facing user applying for adoption          | Landing page, adoption application, view own application status, view available animals              |

### Permission Matrix (Detailed)

| Module               | Super Admin | Shelter Head | Veterinarian | Shelter Staff | Billing Clerk | Adopter |
| -------------------- | :---------: | :----------: | :----------: | :-----------: | :-----------: | :-----: |
| Dashboard            | Full        | Full         | Medical Only | Operational   | Financial     | —       |
| Animal Intake        | CRUD        | Read         | Read         | CRUD          | —             | —       |
| Medical Records      | CRUD        | Read         | CRUD         | Read          | —             | —       |
| Kennel Management    | CRUD        | Read         | Read         | CRUD          | —             | —       |
| Adoption Management  | CRUD        | Approve      | —            | CRUD          | Read          | Apply   |
| Billing & Invoices   | CRUD        | Read         | —            | Create        | CRUD          | View Own|
| Inventory            | CRUD        | Read         | Med. Supplies| CRUD          | —             | —       |
| User Management      | CRUD        | Read         | —            | —             | —             | —       |
| Reports & Export     | Full        | Full         | Medical Only | Operational   | Financial     | —       |
| System Settings      | Full        | Read         | —            | —             | —             | —       |

---

## 5. Functional Requirements

---

### 5.1 Landing Page (Public-Facing)

> A visually stunning, responsive landing page that serves as the public face of the Catarman Dog Pound.

#### 5.1.1 Hero Section
- Full-width hero banner with high-quality shelter imagery
- Tagline: *"Every Animal Deserves a Home"* (or shelter-chosen copy)
- Primary CTA buttons: **"View Available Animals"** and **"Start Adoption Application"**
- Smooth scroll-down animations and parallax effects

#### 5.1.2 Available Animals Gallery
- Filterable card grid showing animals available for adoption
- Each card shows: photo, name, breed, age, gender, size, and status badge
- Click-to-expand modal with full animal details
- Search bar with filters: species, breed, age range, size, gender

#### 5.1.3 Adoption Process Overview
- Visual step-by-step timeline of the adoption process:
  1. Submit Application → 2. Interview Scheduled → 3. Attend Seminar → 4. Billing & Payment → 5. Take Home
- Each step has an icon, title, and short description

#### 5.1.4 About the Shelter
- Brief history, mission, and vision of the Catarman Dog Pound
- Operating hours, location with embedded map, contact information

#### 5.1.5 Adopter Login / Registration
- Clean login form with email/password
- Registration form for new adopters: name, email, phone, address, valid ID upload
- Password strength indicator, terms & conditions checkbox

#### 5.1.6 Footer
- Quick links, social media icons, contact info, privacy policy link

---

### 5.2 Authentication & User Account Management

#### 5.2.1 Authentication
- **Login:** Email + password with bcrypt hashing (minimum 12 rounds)
- **Session management:** Secure HTTP-only, SameSite=Strict server-side sessions with active-session validation
- **Multi-factor authentication (optional):** Email-based OTP for admin roles
- **Password reset:** Secure token-based flow:
  1. User requests reset → system generates cryptographically secure token (expires in 15 minutes)
  2. Token sent via email link
  3. User sets new password → all existing sessions invalidated
  4. Token is single-use and hashed in DB (never stored in plain text)
- **Account lockout:** After 5 failed login attempts, lock account for 15 minutes
- **Password policy:** Minimum 8 characters, at least 1 uppercase, 1 lowercase, 1 number, 1 special character

#### 5.2.2 User Account Management (Admin)
- **Soft deletes:** Deactivated accounts are flagged (`is_deleted = true`, `deleted_at = timestamp`), never hard-deleted
  - Soft-deleted users cannot log in
  - Data remains in the database for audit trails
  - Super Admin can restore soft-deleted accounts within 90 days
- **Data backups:**
  - Automated daily database backups (full)
  - Weekly incremental backups
  - Backup files encrypted at rest and stored in separate location
  - One-click restore from backup (Super Admin only)
  - Backup integrity verification (checksum validation)

#### 5.2.3 Audit Trail
- Every create, update, and delete action logs: `user_id`, `action`, `module`, `record_id`, `old_value`, `new_value`, `timestamp`, `ip_address`
- Audit logs are immutable (append-only, no updates or deletes)

---

### 5.3 Dashboard (Shelter Head / Admin)

> A beautiful, data-rich executive dashboard providing a real-time overview of shelter operations.

#### 5.3.1 Key Metrics Cards (Top Row)
| Card                      | Data Source         | Visual              |
| ------------------------- | ------------------- | ------------------- |
| Total Animals In Shelter  | Animal Intake       | Number + trend arrow |
| Available Kennels         | Kennel Management   | Number / capacity % |
| Pending Adoptions         | Adoption Pipeline   | Number + urgency    |
| Monthly Revenue           | Billing             | Currency + trend    |
| Low-Stock Alerts          | Inventory           | Count + badge       |
| Active Medical Cases      | Medical Records     | Number              |

#### 5.3.2 Charts & Visualizations
- **Animal Intake Trend:** Line chart showing daily/weekly/monthly intake over last 12 months
- **Adoption Rate:** Bar chart comparing intakes vs. adoptions vs. euthanasia vs. transfers per month
- **Kennel Occupancy:** Donut chart showing occupied vs. available vs. maintenance kennels
- **Revenue Breakdown:** Stacked bar chart by category (adoption fees, medical fees, surrender fees, fines)
- **Species Distribution:** Pie chart of current animal population by species
- **Adoption Pipeline Funnel:** Funnel chart showing applications → interviews → seminars → completed adoptions

#### 5.3.3 Recent Activity Feed
- Real-time scrolling feed showing recent system actions:
  - "Staff A admitted a Labrador (ID: A-2026-0034) at 9:15 AM"
  - "Dr. B completed vaccination for Poodle (ID: A-2026-0012) at 10:30 AM"
  - "Adopter C's application moved to Interview stage"
- Clickable entries that navigate to relevant records

#### 5.3.4 Quick Actions
- Buttons for frequent tasks: "New Intake", "Process Adoption", "Generate Report"
- Notification bell with unread count for pending items requiring attention

#### 5.3.5 Dashboard Filters
- Date range picker (today, this week, this month, custom range)
- Filter by species, staff member, or module

---

### 5.4 Animal Intake Module

#### 5.4.1 Intake Registration Form
- **Animal Information:**
  - Auto-generated **Animal ID** (format: `A-YYYY-NNNN`, e.g., `A-2026-0001`)
  - Species (Dog, Cat, Other — with custom input for "Other")
  - Breed (searchable dropdown populated from breed database + "Unknown/Mixed" option)
  - Name (optional, can be assigned by shelter)
  - Age (estimated if unknown): years/months selector
  - Gender: Male / Female
  - Color/Markings: text input + color picker
  - Size: Small / Medium / Large / Extra Large
  - Weight (kg): numeric input
  - Distinguishing features: textarea
  - Photo upload: up to 5 photos (compressed, max 5MB each)
- **Intake Details:**
  - Intake type: Stray / Owner Surrender / Confiscated / Transfer / Born in Shelter
  - Intake date & time (auto-filled, editable)
  - Location found (for strays): address or map pin
  - Brought in by: name, contact number, address (required for surrenders)
  - Reason for surrender (if applicable): dropdown + textarea
  - Condition at intake: Healthy / Injured / Sick / Malnourished / Aggressive
  - Initial temperament assessment: Friendly / Shy / Aggressive / Unknown
  - Assigned kennel (auto-suggested based on availability, species, size — editable)

#### 5.4.2 QR Code Generation & Scanning

##### Auto-Generation
- Upon successful registration, the system **automatically generates a unique QR code** encoded with:
  - Animal ID
  - Direct URL to the animal's profile page (e.g., `https://shelter.catarman.gov/animals/A-2026-0001`)
- QR code is:
  - Displayed on screen immediately after intake
  - Downloadable as PNG (high-res 300 DPI for printing)
  - Printable on a **tag label** (standard 2" x 1" label format) for physical attachment to kennel or collar
  - Stored in the database linked to the animal record

##### QR Code Scanning
- **Built-in QR scanner** accessible from any page via a floating action button (camera icon)
- Uses the device camera (mobile or webcam) via `navigator.mediaDevices.getUserMedia()`
- On successful scan:
  - Validates the QR code format
  - Redirects to the animal's full profile page
  - Shows quick-action overlay: "View Records", "Add Medical Entry", "Update Status"
- Fallback: Manual entry of Animal ID if camera is unavailable

#### 5.4.3 Animal Profile Page
- Comprehensive view of all animal data across modules:
  - Basic information (from intake)
  - Photo gallery with lightbox viewer
  - QR code display with re-print button
  - Medical history timeline (linked from Medical Records module)
  - Kennel assignment history
  - Adoption status/history
  - Billing records associated with this animal
  - Status badge: Available / Under Medical Care / In Adoption Process / Adopted / Deceased / Transferred
- Edit button (role-restricted) for updating information
- Status change dropdown with confirmation dialog and reason field

---

### 5.5 Medical Records Module

> Dynamic forms that adapt based on the selected procedure type. Each procedure type renders a **specific form** with fields relevant to that procedure.

#### 5.5.1 Medical Record Entry Point
- Select animal (searchable by ID, name, or QR scan)
- Select procedure type from categorized dropdown → **this selection determines which form loads**

#### 5.5.2 Procedure Types & Dynamic Forms

Each procedure type below renders a **distinct form page** with fields specific to that procedure:

##### A. Vaccination Record Form
| Field                  | Type             | Notes                              |
| ---------------------- | ---------------- | ---------------------------------- |
| Vaccine Name           | Dropdown         | Anti-rabies, DHPP, FVRCP, etc.     |
| Vaccine Brand          | Text             |                                    |
| Batch/Lot Number       | Text             | For recall tracking                |
| Dosage (mL)            | Numeric          |                                    |
| Route of Administration| Dropdown         | Subcutaneous, Intramuscular, Oral  |
| Injection Site         | Dropdown         | Left shoulder, right hip, etc.     |
| Dose Number            | Numeric          | 1st, 2nd, 3rd, booster             |
| Next Due Date          | Date picker      | Auto-calculated based on vaccine schedule |
| Adverse Reactions      | Textarea         | Optional                           |
| Administered By        | Auto-filled      | Logged-in vet                      |
| Date & Time            | Auto-filled      | Editable                           |

##### B. Surgical Procedure Form
| Field                  | Type             | Notes                              |
| ---------------------- | ---------------- | ---------------------------------- |
| Surgery Type           | Dropdown         | Spay, Neuter, Tumor Removal, Amputation, Wound Repair, Other |
| Pre-operative Weight   | Numeric (kg)     |                                    |
| Anesthesia Type        | Dropdown         | General, Local, Sedation           |
| Anesthesia Drug & Dose | Text + Numeric   |                                    |
| Duration (minutes)     | Numeric          |                                    |
| Surgical Notes         | Rich textarea    | Detailed procedure description     |
| Complications          | Textarea         | Optional                           |
| Post-op Instructions   | Textarea         |                                    |
| Follow-up Date         | Date picker      |                                    |
| Surgeon                | Auto-filled      |                                    |

##### C. General Examination Form
| Field                  | Type             | Notes                              |
| ---------------------- | ---------------- | ---------------------------------- |
| Weight (kg)            | Numeric          |                                    |
| Temperature (°C)       | Numeric          |                                    |
| Heart Rate (bpm)       | Numeric          |                                    |
| Respiratory Rate       | Numeric          |                                    |
| Body Condition Score   | Dropdown (1-9)   | Standard BCS scale                 |
| Eyes                   | Dropdown + Notes | Normal / Abnormal + details        |
| Ears                   | Dropdown + Notes |                                    |
| Teeth/Gums             | Dropdown + Notes |                                    |
| Skin/Coat              | Dropdown + Notes |                                    |
| Musculoskeletal        | Dropdown + Notes |                                    |
| Overall Assessment     | Textarea         |                                    |
| Recommendations        | Textarea         |                                    |

##### D. Treatment/Medication Form
| Field                  | Type             | Notes                              |
| ---------------------- | ---------------- | ---------------------------------- |
| Diagnosis              | Text             |                                    |
| Medication Name        | Searchable dropdown | From inventory                  |
| Dosage                 | Text             | e.g., "250mg twice daily"          |
| Route                  | Dropdown         | Oral, Injection, Topical, IV       |
| Frequency              | Dropdown         | Once daily, Twice daily, etc.      |
| Duration (days)        | Numeric          |                                    |
| Start Date             | Date picker      |                                    |
| End Date               | Auto-calculated  |                                    |
| Quantity Dispensed      | Numeric          | Auto-deducts from inventory        |
| Special Instructions   | Textarea         |                                    |

##### E. Deworming Record Form
| Field                  | Type             | Notes                              |
| ---------------------- | ---------------- | ---------------------------------- |
| Dewormer Name          | Dropdown         |                                    |
| Brand                  | Text             |                                    |
| Dosage                 | Text             | Based on weight                    |
| Weight at Treatment    | Numeric          |                                    |
| Next Deworming Due     | Date picker      | Auto-calculated (every 3 months)   |

##### F. Euthanasia Record Form
| Field                  | Type             | Notes                              |
| ---------------------- | ---------------- | ---------------------------------- |
| Reason                 | Dropdown         | Medical, Behavioral, Legal/Court Order, Population Management |
| Reason Details         | Textarea         | Required                           |
| Authorization          | Dropdown         | Selecting authorizing officer      |
| Method                 | Dropdown         | IV Injection (Pentobarbital), etc. |
| Drug & Dosage          | Text + Numeric   |                                    |
| Confirmation of Death  | Checkbox + Time  |                                    |
| Disposal Method        | Dropdown         | Cremation, Burial                  |
| Performed By           | Auto-filled      |                                    |

#### 5.5.3 Medical History Timeline
- Chronological timeline view of all medical entries for an animal
- Color-coded by procedure type
- Expandable entries showing full form data
- Filter by procedure type or date range
- Print-friendly view for veterinary handoffs

---

### 5.6 Kennel Management Module

#### 5.6.1 Kennel Map / Floor Plan View
- Interactive visual grid/map of all kennels in the shelter
- Each kennel displayed as a card/cell showing:
  - Kennel number/ID
  - Status color: 🟢 Available | 🔵 Occupied | 🟡 Under Maintenance | 🔴 Quarantine
  - If occupied: animal name, species, photo thumbnail, days housed
- Click on kennel → opens kennel detail panel

#### 5.6.2 Kennel Details
- Kennel information: ID, location/zone (e.g., "Building A, Row 2"), size category, type (Indoor/Outdoor)
- Current occupant(s) with link to animal profile
- Occupancy history log
- Maintenance log with scheduled cleaning dates
- Capacity: intended species & size range

#### 5.6.3 Kennel Assignment
- Assign animal to kennel from intake form or kennel map
- System auto-suggests available kennels based on: species, size, temperament, quarantine status
- Drag-and-drop reassignment between kennels (on the map view)
- Bulk assignment for batch intakes
- Transfer log: every move recorded with timestamp and reason

#### 5.6.4 Kennel Statistics
- Total capacity vs. current occupancy
- Average length of stay per kennel
- Kennels requiring maintenance alerts

---

### 5.7 Adoption Management Module

> A structured multi-step pipeline: **Application → Interview → Seminar → Billing → Completion**

#### 5.7.1 Adoption Pipeline Overview (Staff View)
- Kanban-style board with columns for each stage:
  1. **New Applications** — Awaiting review
  2. **Interview Scheduled** — Date/time confirmed
  3. **Interview Completed** — Passed / Failed
  4. **Seminar Scheduled** — Date/time confirmed
  5. **Seminar Completed** — Attended / No-show
  6. **Pending Payment** — Invoice generated
  7. **Completed** — Animal released to adopter
  8. **Rejected / Withdrawn** — With reason
- Drag-and-drop cards between stages (with validation — can't skip stages)
- Each card shows: adopter name, animal name, current stage, days in pipeline

#### 5.7.2 Step 1: Adoption Application (Adopter-Facing)
- Adopter fills out application form from the landing page:
  - Personal info: full name, age, address, contact, email, occupation
  - Valid ID upload (government-issued)
  - Housing: type (house, apartment, condo), owned/rented, yard (yes/no, size)
  - Household: number of adults, children (ages), existing pets (species, breed, age, vaccinated?)
  - Animal preference: species, breed, age range, size, gender (or "no preference")
  - Selected animal (if browsing and choosing a specific one)
  - Experience: previous pet ownership history
  - Veterinarian reference (optional): name, clinic, contact
  - Agreement checkboxes: shelter policies, home visit consent, return policy
  - Digital signature (canvas-based)
- Application submitted → status = "Pending Review"
- Staff receives notification of new application

#### 5.7.3 Step 2: Interview Scheduling & Conduct
- Staff reviews application and decides: **Approve for Interview** or **Reject** (with reason)
- If approved:
  - Staff selects available date/time slots from a scheduling calendar
  - Adopter receives email/SMS notification with interview details
  - Interview can be: In-person or Video call (link auto-generated)
- Interview form (filled by staff during interview):
  - Checklist of screening questions
  - Home environment assessment
  - Pet care knowledge evaluation
  - Overall score / recommendation: Approve / Conditional / Reject
  - Notes: textarea
- Result: **Pass** → moves to Seminar stage | **Fail** → moves to Rejected with reason

#### 5.7.4 Step 3: Seminar Scheduling & Attendance
- Seminars are scheduled as group events (multiple adopters per session)
- Seminar management:
  - Create seminar event: date, time, location, capacity, facilitator
  - Assign approved adopters to next available seminar
  - Adopter receives notification with seminar details
- Attendance tracking:
  - Staff marks attendance on seminar day
  - Attended → moves to Billing stage
  - No-show → option to reschedule or withdraw

#### 5.7.5 Step 4: Billing & Payment
- Upon seminar completion, system auto-generates an invoice for the adoption fee
- Invoice includes: adoption fee and any other approved shelter charges
- Payment recorded by billing clerk (see Billing Module 5.8)
- Payment confirmed → moves to Completed stage

#### 5.7.6 Step 5: Adoption Completion
- Final checklist before release:
  - [ ] Payment confirmed
  - [ ] Adoption contract signed (digital signature)
  - [ ] Medical records copy provided to adopter
  - [ ] Spay/neuter compliance agreement (if not yet done)
- Animal status updated to "Adopted"
- Kennel status updated to "Available"
- Adopter receives digital copy of all documents via email
- Adoption certificate generated (PDF, printable)

#### 5.7.7 Adopter Portal (Post-Adoption)
- Adopter can log in to view:
  - Adoption certificate
  - Animal's medical records (read-only)
  - Upcoming vaccination reminders
  - Return/surrender process information

---

### 5.8 Billing & Invoice Module

#### 5.8.1 Fee Configuration (Admin)
- Configurable fee schedule with categories:
  - Adoption fees (by species, breed, age)
  - Surrender fees
  - Impound fees (daily rate)
  - Medical procedure fees
  - Licensing/registration fees
  - Fines (e.g., leash law violations)
- Fee history: track changes over time with effective dates

#### 5.8.2 Invoice Generation
- **Auto-generated** invoices triggered by:
  - Adoption completion (Step 4 of adoption pipeline)
  - Medical procedures performed
  - Impound release
  - Owner surrender
- **Manual** invoice creation for ad-hoc charges
- Invoice fields:
  - Auto-incremented invoice number (format: `INV-YYYY-NNNN`)
  - Date issued
  - Payor info (adopter name, contact, address)
  - Line items table: description, quantity, unit price, total
  - Subtotal, applicable tax/fees, grand total
  - Payment status: Unpaid / Partially Paid / Paid
  - Due date
  - Notes/terms
- Invoice output: viewable on screen, downloadable as **PDF**, printable

#### 5.8.3 Payment Recording
- Record payment against an invoice:
  - Amount paid
  - Payment method: Cash / Bank Transfer / GCash / Maya / Check
  - Reference number (for digital payments)
  - Date of payment
  - Received by (auto-filled)
- Partial payment support with running balance
- Official receipt generation upon full payment

#### 5.8.4 Billing Dashboard
- Outstanding invoices list with aging (0-30, 31-60, 60+ days)
- Revenue summary by period (daily, weekly, monthly)
- Payment method breakdown chart

---

### 5.9 Inventory Management Module

#### 5.9.1 Inventory Categories
- **Medical Supplies:** vaccines, medications, surgical supplies, syringes, antiseptics
- **Food & Nutrition:** dog food, cat food, supplements, treats
- **Cleaning & Maintenance:** disinfectants, cleaning tools, bedding, kennel supplies
- **Office Supplies:** labels, printer ink, paper, QR code tags
- **Equipment:** leashes, muzzles, carriers, cages

#### 5.9.2 Inventory Item Management
- Add new item: name, SKU, category, unit of measure, supplier, cost per unit, reorder level, location/shelf
- Edit item details
- Soft-delete discontinued items

#### 5.9.3 Stock Transactions
- **Stock In:** purchase orders, donations, returns
  - Fields: item, quantity, source, date, received by, batch/lot number (for medical supplies), expiry date
- **Stock Out:** usage, dispensing (auto-triggered from Medical Records), wastage, transfer
  - Fields: item, quantity, reason, related animal ID (if applicable), date, issued by
- **Adjustments:** manual corrections with reason (e.g., "physical count discrepancy")

#### 5.9.4 Stock Monitoring
- Current stock levels table: item, category, quantity on hand, reorder level, status
- **Low-stock alerts:** items below reorder level highlighted in red, notification sent to relevant staff
- **Expiry tracking:** items nearing expiration (< 30 days) flagged with warnings
- Stock movement history per item

#### 5.9.5 Inventory Reports
- Stock summary report (current levels, value)
- Consumption report by category/period
- Expiry report
- Supplier-wise purchase report

---

### 5.10 User Management Module (Admin Only)

#### 5.10.1 User CRUD Operations
- Create user: name, email, phone, role assignment, temporary password (forced change on first login)
- View user list with search, filter by role/status, sort
- Edit user profile and role
- Soft-delete user (sets `is_deleted = true`, deactivates login)
- Restore soft-deleted user (within 90-day window)
- View user activity log (filtered audit trail)

#### 5.10.2 Role Management
- View all roles and their permissions
- Custom role creation (if needed beyond default roles)
- Assign/revoke permissions per role
- Role change audit logging

#### 5.10.3 User Session Management
- View active sessions per user
- Force logout / invalidate sessions (Super Admin)
- Session timeout configuration

---

### 5.11 Reports & Export Module

#### 5.11.1 Standard Reports

| Report Name                        | Data Source        | Filters                        | Export Formats |
| ---------------------------------- | ------------------ | ------------------------------ | -------------- |
| Animal Intake Summary              | Animal Intake      | Date range, species, intake type | PDF, CSV, Excel |
| Individual Animal Record           | All modules        | Animal ID                      | PDF            |
| Medical Records Summary            | Medical Records    | Date range, procedure type, vet| PDF, CSV       |
| Adoption Pipeline Report           | Adoption Mgmt     | Date range, stage, status      | PDF, CSV       |
| Kennel Occupancy Report            | Kennel Mgmt        | Date, zone                     | PDF, CSV       |
| Revenue & Billing Report           | Billing            | Date range, category, payment status | PDF, CSV, Excel |
| Inventory Stock Report             | Inventory          | Category, stock status         | PDF, CSV, Excel |
| User Activity Audit Report         | Audit Trail        | Date range, user, module       | PDF, CSV       |
| Animal Population Census           | Animal Intake      | As-of date, species, status    | PDF            |
| Adoption Success Rate Report       | Adoption Mgmt      | Date range                     | PDF, CSV       |

#### 5.11.2 Individual Animal Export
- Full animal dossier: intake details, all medical records, kennel history, adoption status, billing records
- Exported as a single, formatted **PDF document** with shelter letterhead
- QR code printed on the document for quick re-access

#### 5.11.3 Report Builder (Advanced)
- Custom date range selector
- Column picker (choose which fields to include)
- Grouping & aggregation options
- Save report configurations as templates for reuse

#### 5.11.4 Scheduled Reports (Optional Enhancement)
- Configure automatic report generation and email delivery
- Weekly/monthly operational summaries to Shelter Head

---

## 6. Non-Functional / Production Requirements

### 6.1 Role-Based Access Control (RBAC)
- Middleware-based role and permission checks on every API route
- Frontend route guards preventing unauthorized page access
- API returns `403 Forbidden` for unauthorized actions (never exposes data)
- Permissions are checked at both **API middleware** and **database query** levels

### 6.2 Authorization
- Server-side session authentication backed by the `user_sessions` table
- Session identifiers stored in HTTP-only, Secure, SameSite=Strict cookies
- Password changes, logout, and admin session invalidation remove active sessions from the server-side store
- All authenticated API endpoints validate the active session before processing

### 6.3 Input Validation & Sanitization
- **Server-side validation** on ALL inputs (never trust the client):
  - Type checking (string, number, date, email, etc.)
  - Length limits (e.g., name: max 100 chars, textarea: max 5000 chars)
  - Format validation (email regex, phone format, date format)
  - Enum validation (dropdown values match allowed list)
  - File upload validation: type (JPEG, PNG, PDF only), size limit (5MB), malware scan
- **Sanitization:**
  - HTML entity encoding to prevent XSS
  - SQL parameterized queries / prepared statements (prevent SQL injection)
  - Path traversal prevention on file operations
- **Client-side validation** mirrors server-side for UX (but never relied upon for security)

### 6.4 CORS Policy
```
Allowed Origins: only the production frontend domain + staging domain
Allowed Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Allowed Headers: Content-Type, Accept, X-CSRF-TOKEN
Credentials: true
Max-Age: 86400 (24 hours preflight cache)
```
- No wildcard (`*`) origins in production
- CORS errors logged for monitoring

### 6.5 Rate Limiting
| Endpoint Category     | Limit                  | Window    | Response on Exceed         |
| --------------------- | ---------------------- | --------- | -------------------------- |
| Login                 | 5 requests             | 15 min    | 429 + account lockout      |
| Password Reset        | 3 requests             | 1 hour    | 429 + silent (no info leak)|
| API (authenticated)   | 100 requests           | 1 min     | 429 + Retry-After header   |
| API (public/adopter)  | 30 requests            | 1 min     | 429 + Retry-After header   |
| File Upload           | 10 requests            | 5 min     | 429                        |

- Rate limiting implemented via Redis-backed token bucket or sliding window algorithm
- Rate limit headers included in responses: `X-RateLimit-Limit`, `X-RateLimit-Remaining`, `X-RateLimit-Reset`

### 6.6 Password Reset Security
- Tokens are **cryptographically random** (minimum 32 bytes, hex-encoded)
- Tokens are **hashed** (SHA-256) before database storage
- Token expiry: **15 minutes** maximum
- **Single-use:** token invalidated after successful reset
- **All sessions invalidated** after password change
- No user enumeration: same response whether email exists or not ("If an account exists, a reset link has been sent")
- Rate limited: max 3 reset requests per email per hour

### 6.7 Error Handling
- **Users see clean fallback pages:**
  - `400` — "Something went wrong with your request. Please try again."
  - `401` — Redirect to login page
  - `403` — "You don't have permission to access this page."
  - `404` — Custom "Page Not Found" page with navigation links
  - `429` — "Too many requests. Please wait and try again."
  - `500` — "We're experiencing technical difficulties. Please try again later." + contact info
- **Error details are NEVER exposed to users** (no stack traces, no SQL errors, no internal paths)
- **Server-side:** full error details logged with request ID, timestamp, stack trace, user context
- **Global error boundary** on the frontend catches unhandled errors and displays fallback UI
- Each error response includes a unique **error reference ID** for support troubleshooting

### 6.8 Database Indexes

```sql
-- High-frequency query indexes
CREATE INDEX idx_animals_status ON animals(status);
CREATE INDEX idx_animals_species ON animals(species);
CREATE INDEX idx_animals_intake_date ON animals(intake_date);
CREATE INDEX idx_animals_animal_id ON animals(animal_id);  -- Unique, for QR lookups

CREATE INDEX idx_medical_records_animal_id ON medical_records(animal_id);
CREATE INDEX idx_medical_records_procedure_type ON medical_records(procedure_type);
CREATE INDEX idx_medical_records_created_at ON medical_records(created_at);

CREATE INDEX idx_adoptions_status ON adoptions(status);
CREATE INDEX idx_adoptions_adopter_id ON adoptions(adopter_id);
CREATE INDEX idx_adoptions_animal_id ON adoptions(animal_id);

CREATE INDEX idx_invoices_status ON invoices(payment_status);
CREATE INDEX idx_invoices_created_at ON invoices(created_at);
CREATE INDEX idx_invoices_adopter_id ON invoices(adopter_id);

CREATE INDEX idx_kennels_status ON kennels(status);
CREATE INDEX idx_kennels_species_size ON kennels(allowed_species, size_category);

CREATE INDEX idx_inventory_category ON inventory_items(category);
CREATE INDEX idx_inventory_stock_level ON inventory_items(quantity_on_hand);
CREATE INDEX idx_inventory_expiry ON inventory_items(expiry_date);

CREATE INDEX idx_users_email ON users(email);  -- Unique
CREATE INDEX idx_users_role ON users(role);
CREATE INDEX idx_users_is_deleted ON users(is_deleted);

CREATE INDEX idx_audit_logs_user_id ON audit_logs(user_id);
CREATE INDEX idx_audit_logs_module ON audit_logs(module);
CREATE INDEX idx_audit_logs_created_at ON audit_logs(created_at);

-- Composite indexes for common query patterns
CREATE INDEX idx_animals_species_status ON animals(species, status);
CREATE INDEX idx_medical_animal_date ON medical_records(animal_id, created_at DESC);
CREATE INDEX idx_adoptions_status_date ON adoptions(status, created_at DESC);
```

### 6.9 Logging (Structured Production Logging)

- **Structured JSON logging** format for all server-side logs:
```json
{
  "timestamp": "2026-03-23T09:15:30.123Z",
  "level": "INFO",
  "message": "Animal intake completed",
  "module": "animal_intake",
  "user_id": "USR-001",
  "animal_id": "A-2026-0034",
  "request_id": "req_abc123",
  "ip": "192.168.1.100",
  "duration_ms": 245
}
```
- **Log levels:** ERROR, WARN, INFO, DEBUG (DEBUG disabled in production)
- **What to log:**
  - All API requests (method, path, status code, response time)
  - Authentication events (login, logout, failed attempts, password resets)
  - CRUD operations on critical entities (animals, adoptions, medical records, billing)
  - Error details with stack traces (ERROR level)
  - Rate limit triggers (WARN level)
  - Performance anomalies (response time > 2 seconds)
- **What NOT to log:**
  - Passwords or tokens (even in error logs)
  - Full credit card / payment details
  - Personal information beyond user_id in standard logs
- **Log storage:** Centralized log aggregation (ELK Stack, or cloud-based logging service)
- **Log retention:** 90 days in hot storage, 1 year in cold/archive storage

### 6.10 Rollback Strategy — Blue-Green Deployment

#### Architecture
```
                    ┌─────────────────┐
                    │   Load Balancer  │
                    │   (Nginx/ALB)    │
                    └────────┬────────┘
                             │
              ┌──────────────┼──────────────┐
              │              │              │
     ┌────────▼────────┐    │    ┌─────────▼────────┐
     │  🟦 BLUE ENV    │    │    │  🟩 GREEN ENV     │
     │  (Current Live) │    │    │  (New Version)    │
     │  v1.2.0         │    │    │  v1.3.0           │
     └────────┬────────┘    │    └─────────┬────────┘
              │              │              │
              └──────────────┼──────────────┘
                             │
                    ┌────────▼────────┐
                    │   Database      │
                    │   (Shared)      │
                    └─────────────────┘
```

#### Deployment Process
1. **Pre-deployment:**
   - Database backup (automated, verified)
   - Database migration run against shared DB (backward-compatible migrations only)
   - New version deployed to **inactive** environment (Green)
   - Automated smoke tests run against Green

2. **Switch (Cutover):**
   - Load balancer switches traffic from Blue → Green
   - Zero-downtime switch (connection draining on Blue)

3. **Post-deployment monitoring (15-minute window):**
   - Monitor: error rates, response times, key transaction success rates
   - Health check endpoints: `/api/health` (basic), `/api/health/deep` (DB + cache connectivity)

4. **Rollback triggers (automatic or manual):**
   - Error rate > 1% of requests
   - Average response time > 3 seconds
   - Health check failures
   - Critical bug reported

5. **Rollback procedure:**
   - Switch load balancer back to Blue (< 30 seconds)
   - If database migration needs reversal: run backward migration script
   - Post-rollback verification tests

#### Database Migration Rules
- All migrations must be **backward-compatible** (additive only during deployment window)
- No column deletions or renames during deployment
- Destructive schema changes done in a separate maintenance window after successful deployment verification

---

## 7. Design System — Minimalist

> [!IMPORTANT]
> **Design Philosophy: Minimalist.** Every UI element must follow this design system exactly. No decorative clutter — only purposeful, clean interfaces with generous whitespace, clear hierarchy, and subtle depth.

### 7.1 Design Tokens (CSS Custom Properties)

> AI IMPLEMENTATION INSTRUCTION: Copy these into `public/assets/css/variables.css` verbatim. Every component must reference these tokens — never use hardcoded color/size values.

```css
:root {
  /* === COLOR PALETTE — Neutral Minimalist === */
  --color-bg-primary: #FFFFFF;
  --color-bg-secondary: #F8F9FA;
  --color-bg-tertiary: #F1F3F5;
  --color-bg-elevated: #FFFFFF;
  --color-bg-overlay: rgba(0, 0, 0, 0.5);
  --color-bg-input: #FFFFFF;              /* Form inputs background */
  --color-bg-hover: #F3F4F6;              /* Generic hover state bg */

  --color-text-primary: #1A1A1A;
  --color-text-secondary: #6B7280;
  --color-text-tertiary: #9CA3AF;
  --color-text-inverse: #FFFFFF;
  --color-text-link: #2563EB;

  --color-border-default: #E5E7EB;
  --color-border-light: #F3F4F6;
  --color-border-focus: #2563EB;

  /* === ACCENT COLORS (Used sparingly) === */
  --color-accent-primary: #2563EB;      /* Blue — primary actions */
  --color-accent-primary-hover: #1D4ED8;
  --color-accent-success: #16A34A;      /* Green — success states */
  --color-accent-success-bg: #F0FDF4;
  --color-accent-warning: #F59E0B;      /* Amber — warnings */
  --color-accent-warning-bg: #FFFBEB;
  --color-accent-danger: #DC2626;       /* Red — errors, destructive */
  --color-accent-danger-bg: #FEF2F2;
  --color-accent-info: #0EA5E9;         /* Sky blue — informational */
  --color-accent-info-bg: #F0F9FF;

  /* === TYPOGRAPHY === */
  --font-family-primary: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  --font-family-mono: 'JetBrains Mono', 'Fira Code', monospace;

  --font-size-xs: 0.75rem;    /* 12px */
  --font-size-sm: 0.875rem;   /* 14px */
  --font-size-base: 1rem;     /* 16px */
  --font-size-lg: 1.125rem;   /* 18px */
  --font-size-xl: 1.25rem;    /* 20px */
  --font-size-2xl: 1.5rem;    /* 24px */
  --font-size-3xl: 1.875rem;  /* 30px */
  --font-size-4xl: 2.25rem;   /* 36px */

  --font-weight-regular: 400;
  --font-weight-medium: 500;
  --font-weight-semibold: 600;
  --font-weight-bold: 700;

  --line-height-tight: 1.25;
  --line-height-normal: 1.5;
  --line-height-relaxed: 1.75;

  /* === SPACING (8px base grid) === */
  --space-1: 0.25rem;   /* 4px */
  --space-2: 0.5rem;    /* 8px */
  --space-3: 0.75rem;   /* 12px */
  --space-4: 1rem;      /* 16px */
  --space-5: 1.25rem;   /* 20px */
  --space-6: 1.5rem;    /* 24px */
  --space-8: 2rem;      /* 32px */
  --space-10: 2.5rem;   /* 40px */
  --space-12: 3rem;     /* 48px */
  --space-16: 4rem;     /* 64px */

  /* === BORDER RADIUS === */
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-lg: 12px;
  --radius-xl: 16px;
  --radius-full: 9999px;  /* Pill shape */

  /* === SHADOWS (Subtle, minimalist) === */
  --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.04);
  --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.06);
  --shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.08);
  --shadow-xl: 0 8px 32px rgba(0, 0, 0, 0.10);
  --shadow-toast: 0 8px 40px rgba(0, 0, 0, 0.12);

  /* === TRANSITIONS === */
  --transition-fast: 150ms ease;
  --transition-base: 250ms ease;
  --transition-slow: 350ms ease;
  --transition-spring: 500ms cubic-bezier(0.34, 1.56, 0.64, 1);

  /* === Z-INDEX LAYERS === */
  --z-dropdown: 100;
  --z-sticky: 200;
  --z-modal-backdrop: 300;
  --z-modal: 400;
  --z-toast: 500;
  --z-tooltip: 600;

  /* === THEME TRANSITION === */
  --theme-transition: background-color 200ms ease, color 200ms ease, border-color 200ms ease, box-shadow 200ms ease;
}

/* ============================================================
   DARK MODE OVERRIDES — Refined Dark UI
   Applied when <html data-theme="dark">

   RULES FOR AI:
   1. NEVER use pure #000000 — use tinted dark grays
   2. Use #FFFFFF at opacity instead of gray hex values
   3. Desaturate accent colors — saturated looks dirty on dark
   4. Use blending modes (screen, overlay) for lighting effects
   5. Make interactive states OBVIOUS — add glow, color shifts
   ============================================================ */
[data-theme="dark"] {
  /* === BACKGROUNDS — Tinted black for personality === */
  /* Base uses #03000D (deep indigo-tinted black) for subtle warmth */
  --color-bg-primary: #03000D;          /* Tinted black — NOT pure #000 */
  --color-bg-secondary: #0D0B14;        /* Slightly lifted, same tint */
  --color-bg-tertiary: #161420;          /* Card hover, active rows */
  --color-bg-elevated: #1A1825;          /* Modals, dropdowns, popovers */
  --color-bg-overlay: rgba(3, 0, 13, 0.8);
  --color-bg-input: #0D0B14;            /* Form inputs background */
  --color-bg-hover: #1E1C28;            /* Generic hover state bg */

  /* === TEXT — #FFFFFF at opacity, NOT gray hex values === */
  /* Opacity-based white naturally blends with the tinted background */
  --color-text-primary: rgba(255, 255, 255, 0.92);    /* Headings, primary */
  --color-text-secondary: rgba(255, 255, 255, 0.60);  /* Body, descriptions */
  --color-text-tertiary: rgba(255, 255, 255, 0.38);   /* Placeholders, hints */
  --color-text-inverse: #03000D;
  --color-text-link: #7DB4FA;            /* Desaturated blue link */

  /* === BORDERS — Opacity-based, inherits tint === */
  --color-border-default: rgba(255, 255, 255, 0.08);
  --color-border-light: rgba(255, 255, 255, 0.04);
  --color-border-focus: rgba(125, 180, 250, 0.5);     /* Glow-ready */

  /* === ACCENT COLORS — Desaturated for dark backgrounds === */
  /* Saturated colors look dirty on dark — mute the palette */
  --color-accent-primary: #5B8DEF;       /* Desaturated blue */
  --color-accent-primary-hover: #7DA5F5;
  --color-accent-success: #4ADE80;       /* Muted green */
  --color-accent-success-bg: rgba(74, 222, 128, 0.08);
  --color-accent-warning: #FBBF24;       /* Muted amber */
  --color-accent-warning-bg: rgba(251, 191, 36, 0.08);
  --color-accent-danger: #F87171;        /* Muted red */
  --color-accent-danger-bg: rgba(248, 113, 113, 0.08);
  --color-accent-info: #67E8F9;          /* Muted cyan */
  --color-accent-info-bg: rgba(103, 232, 249, 0.08);

  /* === INTERACTIVE STATES — Glow effects for visibility === */
  --focus-glow: 0 0 0 3px rgba(91, 141, 239, 0.25);          /* Primary focus ring */
  --focus-glow-danger: 0 0 0 3px rgba(248, 113, 113, 0.25);  /* Danger focus */
  --focus-glow-success: 0 0 0 3px rgba(74, 222, 128, 0.25);  /* Success focus */
  --hover-border-glow: rgba(255, 255, 255, 0.15);             /* Border brighten on hover */

  /* === SHADOWS — Tinted, not pure black === */
  --shadow-sm: 0 1px 3px rgba(3, 0, 13, 0.4);
  --shadow-md: 0 2px 10px rgba(3, 0, 13, 0.5);
  --shadow-lg: 0 4px 24px rgba(3, 0, 13, 0.55);
  --shadow-xl: 0 8px 40px rgba(3, 0, 13, 0.6);
  --shadow-toast: 0 8px 40px rgba(3, 0, 13, 0.7);
}

/* === BLENDING MODE UTILITIES (Dark mode only) === */
/* Use these classes for realistic lighting effects in dark mode */
[data-theme="dark"] .blend-screen    { mix-blend-mode: screen; }
[data-theme="dark"] .blend-overlay   { mix-blend-mode: overlay; }
[data-theme="dark"] .blend-soft-light { mix-blend-mode: soft-light; }

/* Subtle top-lighting effect on elevated surfaces */
[data-theme="dark"] .card::before,
[data-theme="dark"] .modal::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg,
    transparent,
    rgba(255, 255, 255, 0.06),
    transparent
  );
  pointer-events: none;
}
[data-theme="dark"] .card,
[data-theme="dark"] .modal { position: relative; }

/* === DARK MODE INTERACTIVE STATES === */
/* Buttons glow on hover/focus — never invisible */
[data-theme="dark"] .btn-primary:hover {
  box-shadow: 0 0 20px rgba(91, 141, 239, 0.3);
}
[data-theme="dark"] .btn-primary:focus-visible {
  box-shadow: var(--focus-glow);
}
[data-theme="dark"] .btn-secondary:hover {
  border-color: var(--hover-border-glow);
  background: var(--color-bg-hover);
}
[data-theme="dark"] .btn-danger:hover {
  box-shadow: 0 0 20px rgba(248, 113, 113, 0.25);
}

/* Inputs glow on focus */
[data-theme="dark"] .input:focus {
  border-color: var(--color-border-focus);
  box-shadow: var(--focus-glow);
}

/* Cards brighten border on hover */
[data-theme="dark"] .card:hover {
  border-color: rgba(255, 255, 255, 0.12);
}

/* Table rows subtle highlight */
[data-theme="dark"] tr:hover td {
  background: var(--color-bg-hover);
}

/* Links underline glow on hover */
[data-theme="dark"] a:hover {
  text-shadow: 0 0 12px rgba(125, 180, 250, 0.3);
}
```

### 7.2 Typography Rules

| Element            | Font Size        | Weight    | Color               | Usage                             |
| ------------------ | ---------------- | --------- | ------------------- | --------------------------------- |
| Page Title (h1)    | `--font-size-3xl`| Bold (700)| `--color-text-primary` | One per page, main heading      |
| Section Title (h2) | `--font-size-2xl`| Semibold  | `--color-text-primary` | Section headings                |
| Subsection (h3)    | `--font-size-xl` | Semibold  | `--color-text-primary` | Sub-headings                    |
| Card Title (h4)    | `--font-size-lg` | Medium    | `--color-text-primary` | Card headers, form group labels |
| Body               | `--font-size-base`| Regular  | `--color-text-primary` | Default text                    |
| Body Small         | `--font-size-sm` | Regular   | `--color-text-secondary`| Helper text, table cells       |
| Caption / Label    | `--font-size-xs` | Medium    | `--color-text-tertiary`| Form labels, metadata          |
| Data / Numbers     | `--font-family-mono` | Medium| `--color-text-primary` | IDs, dates, currency, counts   |

### 7.3 Component Styles

> AI IMPLEMENTATION INSTRUCTION: These are the **exact** CSS patterns to use when building components in `components.css`.

#### Buttons
```css
/* Primary Button */
.btn-primary {
  background: var(--color-accent-primary);
  color: var(--color-text-inverse);
  padding: var(--space-3) var(--space-6);
  border: none;
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.btn-primary:hover { background: var(--color-accent-primary-hover); }

/* Secondary Button — outlined */
.btn-secondary {
  background: transparent;
  color: var(--color-text-primary);
  border: 1px solid var(--color-border-default);
  padding: var(--space-3) var(--space-6);
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: border-color var(--transition-fast), background var(--transition-fast);
}
.btn-secondary:hover { background: var(--color-bg-secondary); border-color: var(--color-text-tertiary); }

/* Danger Button */
.btn-danger {
  background: var(--color-accent-danger);
  color: var(--color-text-inverse);
  padding: var(--space-3) var(--space-6);
  border: none;
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
}
```

#### Cards
```css
.card {
  background: var(--color-bg-elevated);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-lg);
  padding: var(--space-6);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-base);
}
.card:hover { box-shadow: var(--shadow-md); }
```

#### Form Inputs
```css
.input {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  font-size: var(--font-size-sm);
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-md);
  background: var(--color-bg-primary);
  color: var(--color-text-primary);
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}
.input:focus {
  outline: none;
  border-color: var(--color-border-focus);
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}
.input::placeholder { color: var(--color-text-tertiary); }
```

#### Status Badges
```css
.badge { display: inline-flex; align-items: center; gap: var(--space-1); padding: var(--space-1) var(--space-3); border-radius: var(--radius-full); font-size: var(--font-size-xs); font-weight: var(--font-weight-medium); }
.badge-success { background: var(--color-accent-success-bg); color: var(--color-accent-success); }
.badge-warning { background: var(--color-accent-warning-bg); color: var(--color-accent-warning); }
.badge-danger  { background: var(--color-accent-danger-bg);  color: var(--color-accent-danger); }
.badge-info    { background: var(--color-accent-info-bg);    color: var(--color-accent-info); }
```

### 7.4 Toast Notifications — Sileo-Inspired

> Design reference: [sileo.aaryan.design](https://sileo.aaryan.design)

> AI IMPLEMENTATION INSTRUCTION: Build this as a standalone `toast.js` + CSS component in `public/assets/js/` and `public/assets/css/components.css`. The toast is called via `window.toast.success('Title', 'Description')` globally.

#### Visual Design Spec

```
┌──────────────────────────────────────┐
│  ┌──────────────────────────┐        │
│  │ ● Title Text             │ ← PILL │  Pill: rounded-full, sits on top
│  └──────────────────────────┘        │  edge of the content box
│                                      │
│  Description text goes here.         │  Content box: rounded-lg,
│  Can be multi-line if needed.        │  dark background, subtle border
│                                      │
│  [ Action Button ]                   │  Optional action buttons
└──────────────────────────────────────┘
```

#### Toast Types and Colors

| Type    | Icon | Pill BG                  | Pill Icon Color                | Content BG             |
| ------- | ---- | ------------------------ | ------------------------------ | ---------------------- |
| Success | ✓    | `--color-bg-secondary`   | `--color-accent-success` (green) | `--color-bg-primary` |
| Error   | ✕    | `--color-bg-secondary`   | `--color-accent-danger` (red)    | `--color-bg-primary` |
| Warning | !    | `--color-bg-secondary`   | `--color-accent-warning` (amber) | `--color-bg-primary` |
| Info    | ℹ    | `--color-bg-secondary`   | `--color-accent-info` (blue)     | `--color-bg-primary` |
| Loading | ⟳    | `--color-bg-secondary`   | `--color-text-primary` (spin)    | `--color-bg-primary` |

> **Note:** The toast is always dark-themed (Sileo style). In light mode, force dark colors using a `.toast` scope override. In dark mode, the design tokens naturally produce the dark look.

#### HTML Structure
```html
<!-- Toast Container — fixed position, holds all toasts -->
<div id="toast-container" class="toast-container toast-position-bottom-right">

  <!-- Single Toast -->
  <div class="toast toast-success" role="alert" aria-live="polite">
    <div class="toast-pill">
      <span class="toast-icon"><!-- SVG icon --></span>
      <span class="toast-title">Event Created</span>
    </div>
    <div class="toast-content">
      <p class="toast-description">The record has been saved successfully.</p>
      <div class="toast-actions">
        <button class="toast-action-btn">Undo</button>
        <button class="toast-action-btn">View</button>
      </div>
    </div>
  </div>

</div>
```

#### CSS Implementation
```css
/* Toast Container */
.toast-container {
  position: fixed;
  z-index: var(--z-toast);
  display: flex;
  flex-direction: column;
  gap: var(--space-3);
  pointer-events: none;
  max-width: 380px;
  width: 100%;
}
.toast-position-bottom-right { bottom: var(--space-6); right: var(--space-6); align-items: flex-end; }
.toast-position-top-right    { top: var(--space-6);    right: var(--space-6); align-items: flex-end; }
.toast-position-top-center   { top: var(--space-6);    left: 50%; transform: translateX(-50%); align-items: center; }

/* Single Toast — ALWAYS dark themed (Sileo-inspired) */
/* Uses forced dark values so it stays dark in both light and dark mode */
.toast {
  pointer-events: auto;
  background: var(--toast-bg, #03000D);   /* Tinted black from dark mode palette */
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: var(--radius-lg);
  padding: 0;
  box-shadow: var(--shadow-toast);
  overflow: hidden;
  animation: toast-slide-in var(--transition-spring) forwards;
  max-width: 380px;
  /* Force dark toast colors regardless of theme */
  --toast-bg: #03000D;
  --toast-pill-bg: #0D0B14;
  --toast-text: rgba(255, 255, 255, 0.92);
  --toast-text-muted: rgba(255, 255, 255, 0.60);
}

/* Pill Header */
.toast-pill {
  display: inline-flex;
  align-items: center;
  gap: var(--space-2);
  padding: var(--space-2) var(--space-4);
  background: var(--toast-pill-bg);
  border-radius: var(--radius-full);
  margin: var(--space-3) var(--space-3) 0 var(--space-3);
}
.toast-icon { display: flex; align-items: center; width: 16px; height: 16px; }
.toast-title {
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  color: var(--toast-text);
}

/* Content Area */
.toast-content {
  padding: var(--space-3) var(--space-4) var(--space-4);
}
.toast-description {
  font-size: var(--font-size-sm);
  color: var(--toast-text-muted);
  line-height: var(--line-height-normal);
  margin: 0;
}
.toast-actions {
  display: flex;
  gap: var(--space-2);
  margin-top: var(--space-3);
}
.toast-action-btn {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: var(--toast-text);
  padding: var(--space-1) var(--space-3);
  border-radius: var(--radius-sm);
  font-size: var(--font-size-xs);
  cursor: pointer;
  transition: background var(--transition-fast);
}
.toast-action-btn:hover { background: rgba(255, 255, 255, 0.15); }

/* Icon Colors per Type */
.toast-success .toast-icon { color: #16A34A; }
.toast-error   .toast-icon { color: #DC2626; }
.toast-warning .toast-icon { color: #F59E0B; }
.toast-info    .toast-icon { color: #0EA5E9; }

/* === ANIMATIONS === */
@keyframes toast-slide-in {
  0%   { opacity: 0; transform: translateY(16px) scale(0.95); }
  100% { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes toast-slide-out {
  0%   { opacity: 1; transform: translateY(0) scale(1); }
  100% { opacity: 0; transform: translateY(16px) scale(0.95); }
}
.toast-exit { animation: toast-slide-out 300ms ease forwards; }

/* === 3D STACKING (for multiple toasts) === */
.toast:nth-last-child(2) { transform: scale(0.95) translateY(-4px); opacity: 0.7; }
.toast:nth-last-child(3) { transform: scale(0.90) translateY(-8px); opacity: 0.4; }
.toast:nth-last-child(n+4) { display: none; }  /* Max 3 visible */
```

#### JavaScript API
```javascript
// FILE: public/assets/js/toast.js
// Global toast API — usage: toast.success('Title', 'Description')

window.toast = {
  success(title, description = '', options = {}) { /* ... */ },
  error(title, description = '', options = {}) { /* ... */ },
  warning(title, description = '', options = {}) { /* ... */ },
  info(title, description = '', options = {}) { /* ... */ },
  promise(asyncFn, { loading, success, error }) { /* ... */ },
};

// Options shape:
// {
//   duration: 4000,           // Auto-dismiss in ms (0 = sticky)
//   position: 'bottom-right', // bottom-right | top-right | top-center
//   actions: [                // Optional action buttons
//     { label: 'Undo', onClick: () => {} },
//     { label: 'View', onClick: () => {} },
//   ],
// }
```

#### Toast Behavior Rules
- **Default position:** `bottom-right`
- **Auto-dismiss:** 4 seconds (success, info), 6 seconds (warning), sticky until dismissed (error)
- **Max visible:** 3 toasts — older ones stack with scale reduction (0.95, 0.90) and opacity fade
- **Animation:** Slide in from bottom with spring easing (500ms), slide out downward (300ms ease)
- **Dismissal:** Click anywhere on toast to dismiss, or auto-timeout
- **Accessibility:** `role="alert"`, `aria-live="polite"` (assertive for errors)

### 7.5 Minimalist Design Rules (AI Must Follow)

1. **Whitespace is a feature** — Use generous padding (`--space-6` to `--space-8` for sections). Never crowd elements.
2. **One primary action per view** — Only one blue primary button visible. Other actions use secondary/ghost style.
3. **Flat hierarchy** — No nested cards. No gradients. No decorative borders. Depth comes only from subtle shadows on elevation.
4. **Content-first** — No unnecessary icons or decorations. Icons only where they aid comprehension (status badges, nav).
5. **Subtle borders** — 1px `--color-border-default`. Never dark or thick borders.
6. **Hover states** — Cards lift with `shadow-md`. Buttons darken slightly. All transitions use `--transition-fast`.
7. **Color restraint** — Accent colors (green, red, amber, blue) appear only on: status badges, toast icons, primary buttons, and form validation. Background is always white/gray.
8. **Tables** — Clean, borderless rows with `--color-bg-secondary` alternating stripes. No vertical borders.
9. **Modals** — Centered, max-width 560px, `--shadow-xl`, overlay at `--color-bg-overlay`. No title bars — just a close × icon.
10. **Empty states** — Centered illustration + text + single CTA button. Never show a blank page.

### 7.6 Dark / Light Mode Toggle

> AI IMPLEMENTATION INSTRUCTION: Theme preference is stored in `localStorage` and applied via `data-theme` attribute on `<html>`. All color tokens auto-switch — no extra CSS classes needed on individual components.

#### Theme Detection & Initialization
```javascript
// FILE: public/assets/js/theme.js
// Runs BEFORE page render — place in <head> as inline script to prevent flash

(function() {
  const saved = localStorage.getItem('theme');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const theme = saved || (prefersDark ? 'dark' : 'light');
  document.documentElement.setAttribute('data-theme', theme);
})();
```

#### Toggle Button UI
```html
<!-- Place in header.php navbar, right side -->
<button id="theme-toggle" class="theme-toggle" aria-label="Toggle dark mode" title="Toggle theme">
  <svg class="theme-icon theme-icon-sun" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <circle cx="12" cy="12" r="5"/>
    <line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/>
    <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/>
    <line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/>
    <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
  </svg>
  <svg class="theme-icon theme-icon-moon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
  </svg>
</button>
```

#### Toggle CSS
```css
/* Theme toggle button */
.theme-toggle {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid var(--color-border-default);
  border-radius: var(--radius-full);
  background: var(--color-bg-secondary);
  color: var(--color-text-secondary);
  cursor: pointer;
  transition: var(--theme-transition);
}
.theme-toggle:hover {
  background: var(--color-bg-tertiary);
  color: var(--color-text-primary);
}

/* Show/hide sun/moon based on theme */
[data-theme="light"] .theme-icon-sun  { display: none; }
[data-theme="light"] .theme-icon-moon { display: block; }
[data-theme="dark"]  .theme-icon-sun  { display: block; }
[data-theme="dark"]  .theme-icon-moon { display: none; }

/* Smooth theme transition on ALL elements */
*,
*::before,
*::after {
  transition: var(--theme-transition);
}
```

#### Toggle JavaScript
```javascript
// FILE: public/assets/js/theme.js (append after initialization IIFE)

document.addEventListener('DOMContentLoaded', () => {
  const toggle = document.getElementById('theme-toggle');
  if (!toggle) return;

  toggle.addEventListener('click', () => {
    const current = document.documentElement.getAttribute('data-theme');
    const next = current === 'dark' ? 'light' : 'dark';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
  });

  // Listen for OS-level theme changes
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
    if (!localStorage.getItem('theme')) {
      document.documentElement.setAttribute('data-theme', e.matches ? 'dark' : 'light');
    }
  });
});
```

#### Theme Behavior Rules
| Rule | Detail |
| ---- | ------ |
| **Default** | Follow OS preference (`prefers-color-scheme`). If no OS pref, default to `light`. |
| **Persistence** | Save to `localStorage('theme')`. Persists across sessions and page reloads. |
| **No flash** | Inline theme detection script in `<head>` runs before CSS paint. |
| **Transition** | 200ms ease on `background-color`, `color`, `border-color`, `box-shadow`. No jarring instant switch. |
| **Scope** | Every component automatically adapts — they all use CSS variables, no per-component overrides needed. |
| **Toggle position** | In the top navbar, right-aligned, next to the notification bell. |
| **Adopter landing page** | Also supports dark/light mode. Toggle in the top-right of the public navbar. |
| **Charts (Chart.js)** | Re-render with updated colors on theme change. Grid lines: `--color-border-light`. Text: `--color-text-secondary`. |

---

## 8. System Architecture

### Tech Stack (Mandatory)

> [!IMPORTANT]
> This project uses **vanilla technologies only** — no frameworks, no XAMPP, no Laragon. The PHP built-in development server (`php -S`) is used for local development.

| Layer              | Technology                                              |
| ------------------ | ------------------------------------------------------- |
| **Frontend**       | HTML5, vanilla CSS3, vanilla JavaScript (ES6+)          |
| **Backend**        | PHP 8.2+ (vanilla, no framework — custom MVC router)    |
| **Database**       | MySQL 8.0 (standalone installation, no XAMPP/Laragon)   |
| **Dev Server**     | PHP built-in server (`php -S localhost:8000`)            |
| **Sessions/Cache** | PHP native sessions + file-based or MySQL-based cache   |
| **File Storage**   | Local filesystem with organized upload directories      |
| **QR Generation**  | `chillerlan/php-qrcode` (Composer) or `phpqrcode` lib   |
| **QR Scanning**    | `html5-qrcode` JavaScript library (CDN or local)        |
| **PDF Generation** | `TCPDF` or `Dompdf` (Composer)                          |
| **Charts**         | Chart.js (CDN or local) for dashboard visualizations    |
| **Logging**        | Custom PHP logger (writes structured JSON to log files) |
| **Deployment**     | Nginx + PHP-FPM (production), Docker optional           |
| **Dependencies**   | Composer (PHP packages only)                            |

### Composer Dependencies (composer.json)

> AI IMPLEMENTATION INSTRUCTION: Run `composer require` for each production dependency and `composer require --dev` for dev dependencies. Use these exact package names.

```json
{
  "name": "catarman/animal-shelter",
  "description": "Catarman Dog Pound & Animal Shelter Management System",
  "type": "project",
  "require": {
    "php": ">=8.2",
    "vlucas/phpdotenv": "^5.6",
    "chillerlan/php-qrcode": "^5.0",
    "tecnickcom/tcpdf": "^6.7",
    "phpmailer/phpmailer": "^6.9",
    "intervention/image": "^3.0",
    "monolog/monolog": "^3.5"
  },
  "require-dev": {
    "phpunit/phpunit": "^10.5",
    "fakerphp/faker": "^1.23"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

| Package | Version | Purpose |
|---------|---------|--------|
| `vlucas/phpdotenv` | ^5.6 | Load `.env` environment variables |
| `chillerlan/php-qrcode` | ^5.0 | Generate QR codes for animals (PNG output) |
| `tecnickcom/tcpdf` | ^6.7 | Generate invoices, receipts, adoption certificates as PDF |
| `phpmailer/phpmailer` | ^6.9 | Send password reset emails, adoption notifications |
| `intervention/image` | ^3.0 | Resize and compress uploaded animal photos |
| `monolog/monolog` | ^3.5 | Structured JSON logging to files |
| `phpunit/phpunit` | ^10.5 | Unit and integration testing (dev only) |
| `fakerphp/faker` | ^1.23 | Generate fake data for testing/seeding (dev only) |

### Frontend Libraries (CDN or local in `public/assets/vendor/`)

| Library | Version | Purpose | Inclusion |
|---------|---------|---------|----------|
| `Chart.js` | 4.4+ | Dashboard charts (line, bar, donut) | CDN: `cdn.jsdelivr.net/npm/chart.js` |
| `html5-qrcode` | 2.3+ | QR code scanning via camera | CDN: `unpkg.com/html5-qrcode` |
| `Inter` (font) | variable | Primary UI font | Google Fonts |
| `JetBrains Mono` (font) | variable | Monospace for IDs, data | Google Fonts |

### High-Level Architecture

```
┌──────────────────────────────────────────────────────┐
│                    CLIENT LAYER                       │
│             HTML5 + CSS3 + Vanilla JS                 │
│  ┌─────────────┐  ┌──────────────┐  ┌─────────────┐ │
│  │  Landing     │  │  Staff       │  │  Admin      │ │
│  │  Page        │  │  Portal      │  │  Panel      │ │
│  └──────┬──────┘  └──────┬───────┘  └──────┬──────┘ │
└─────────┼────────────────┼─────────────────┼─────────┘
          │                │                 │
          └────────────────┼─────────────────┘
                           │ HTTPS / REST API (JSON)
┌──────────────────────────┼───────────────────────────┐
│              SERVER LAYER (Vanilla PHP)               │
│  ┌───────────────────────▼────────────────────────┐  │
│  │           Custom Router (index.php)            │  │
│  │     (CORS, Rate Limit, Auth Middleware)        │  │
│  └───────────────────────┬────────────────────────┘  │
│  ┌───────┬───────┬───────┼───────┬───────┬────────┐  │
│  │Animal │Medical│Kennel │Adopt- │Billing│ Report │  │
│  │Module │Module │Module │ion    │Module │ Module │  │
│  │       │       │       │Module │       │        │  │
│  └───┬───┴───┬───┴───┬───┴───┬───┴───┬───┴───┬────┘  │
│      │       │       │       │       │       │       │
│  ┌───▼───────▼───────▼───────▼───────▼───────▼────┐  │
│  │     Data Access Layer (PDO Prepared Stmts)      │  │
│  └───────────────────────┬────────────────────────┘  │
└──────────────────────────┼───────────────────────────┘
                           │
┌──────────────────────────┼───────────────────────────┐
│                    DATA LAYER                        │
│  ┌──────────────────┐  ┌─▼────────┐  ┌────────────┐ │
│  │  PHP Sessions     │  │  MySQL   │  │   uploads/ │ │
│  │  (file-based or   │  │  8.0     │  │  (Photos,  │ │
│  │   DB-backed)      │  │          │  │   PDFs)    │ │
│  └──────────────────┘  └──────────┘  └────────────┘ │
└──────────────────────────────────────────────────────┘
```

---

## 9. Mobile Responsive Layout

> All pages must be fully responsive and usable on mobile devices (phones and tablets) in addition to desktop.

### 8.1 Responsive Strategy

- **Mobile-first CSS approach:** Base styles target mobile (320px+), then scale up with `min-width` media queries
- **No CSS frameworks** — all responsive layouts built with vanilla CSS using Flexbox and CSS Grid
- **Breakpoints:**

| Breakpoint        | Width         | Target Devices                    |
| ----------------- | ------------- | --------------------------------- |
| Mobile (default)  | 320px – 767px | Smartphones (portrait & landscape)|
| Tablet            | 768px – 1023px| iPad, Android tablets             |
| Desktop           | 1024px – 1439px| Laptops, standard monitors       |
| Large Desktop     | 1440px+       | Wide monitors, ultra-wide         |

### 8.2 Responsive Layout Rules

#### Navigation
- **Desktop:** Full horizontal navbar with all menu items visible + sidebar for admin panel
- **Tablet:** Collapsible sidebar with hamburger toggle, top navbar condensed
- **Mobile:** Bottom navigation bar (5 key actions) + hamburger menu for full nav
  - Bottom bar icons: Home, Animals, QR Scan, Notifications, Profile

#### Dashboard
- **Desktop:** 3-column metric cards, charts side-by-side, activity feed on right
- **Tablet:** 2-column metric cards, charts stacked, activity feed below
- **Mobile:** Single-column metric cards (horizontally scrollable), charts full-width, activity feed as expandable list

#### Data Tables (Animals, Inventory, Billing, etc.)
- **Desktop:** Full table with all columns visible
- **Tablet:** Horizontal scroll with pinned first column (ID/Name)
- **Mobile:** Card-based layout — each row becomes a stacked card with key fields visible and "tap to expand" for full details

#### Forms (Intake, Medical, Adoption)
- **Desktop:** Multi-column form layout (2-3 fields per row)
- **Tablet:** 2-column form layout
- **Mobile:** Single-column stacked form, full-width inputs, large touch targets (min 44px height)

#### Kennel Map
- **Desktop:** Full grid view with kennel details visible on hover
- **Tablet:** Pinch-to-zoom grid, tap for kennel details
- **Mobile:** Scrollable list view with status badges, option to toggle between list and compact grid

#### Adoption Kanban Board
- **Desktop:** Full horizontal kanban with all columns visible
- **Tablet:** Horizontal scroll kanban
- **Mobile:** Vertical stacked view — one stage at a time with swipe navigation between stages

#### QR Scanner
- **All sizes:** Full-screen camera view with overlay frame, works identically across devices
- **Mobile-optimized:** Uses rear camera by default, includes torch/flash toggle

### 8.3 CSS Implementation Approach

```css
/* Base mobile styles */
.container { width: 100%; padding: 0 16px; }
.grid { display: flex; flex-direction: column; gap: 16px; }

/* Tablet */
@media (min-width: 768px) {
  .container { max-width: 720px; margin: 0 auto; }
  .grid { flex-direction: row; flex-wrap: wrap; }
  .grid > .card { flex: 0 0 calc(50% - 8px); }
}

/* Desktop */
@media (min-width: 1024px) {
  .container { max-width: 1200px; }
  .grid > .card { flex: 0 0 calc(33.333% - 11px); }
}

/* Large Desktop */
@media (min-width: 1440px) {
  .container { max-width: 1400px; }
}
```

### 8.4 Touch & Accessibility
- Minimum touch target size: **44px × 44px** (WCAG 2.1 Level AA)
- Swipe gestures for navigation between views where appropriate
- `viewport` meta tag: `<meta name="viewport" content="width=device-width, initial-scale=1.0">`
- No horizontal overflow — all content contained within viewport width
- Text sizing uses `rem` units with a responsive base `font-size` on `<html>`
- Focus indicators visible on all interactive elements for keyboard navigation
- ARIA labels on icon-only buttons

---

## 10. Database Design Considerations

> [!IMPORTANT]
> The **full production SQL schema** with all CREATE TABLE statements, constraints, indexes, and naming conventions is in:
> **[database_schema.sql](file:///c:/Users/TESS%20LARON/Desktop/REVISED/database_schema.sql)** — 38 tables, ready for migration.

### Key Design Principles
- **Soft deletes** on all primary entities (animals, users, adoptions, invoices) via `is_deleted` flag and `deleted_at` timestamp
- **Audit columns** on every table: `created_at`, `updated_at`, `created_by`, `updated_by`
- **Auto-increment BIGINT UNSIGNED** primary keys with formatted display IDs via `id_sequences` table (A-YYYY-NNNN, INV-YYYY-NNNN)
- **Normalized to 3NF** with strategic denormalization for reporting tables
- **Enum fields** stored as string/varchar (not MySQL ENUM) for flexibility
- **JSON columns** used sparingly: interview screening checklists, report template configs
- **Foreign key constraints** with appropriate `ON DELETE` actions (RESTRICT or SET NULL — never CASCADE on primary records)
- **Computed columns**: `invoice.balance_due` auto-calculated as `total_amount - amount_paid`; `invoice_line_items.total_price` as `quantity * unit_price`

### Table Count by Module (38 total)

| Module           | Count | Tables                                                                                              |
| ---------------- | :---: | --------------------------------------------------------------------------------------------------- |
| Auth & Users     | 6     | `roles`, `permissions`, `role_permissions`, `users`, `user_sessions`, `password_reset_tokens`       |
| Animals          | 4     | `breeds`, `animals`, `animal_photos`, `animal_qr_codes`                                            |
| Kennels          | 3     | `kennels`, `kennel_assignments`, `kennel_maintenance_logs`                                          |
| Medical Records  | 7     | `medical_records`, `vaccination_records`, `surgery_records`, `examination_records`, `treatment_records`, `deworming_records`, `euthanasia_records` |
| Adoptions        | 5     | `adoption_applications`, `adoption_interviews`, `adoption_seminars`, `seminar_attendees`, `adoption_completions` |
| Billing          | 4     | `fee_schedule`, `invoices`, `invoice_line_items`, `payments`                                        |
| Inventory        | 3     | `inventory_categories`, `inventory_items`, `stock_transactions`                                     |
| System & Audit   | 6     | `audit_logs`, `notifications`, `system_backups`, `report_templates`, `rate_limit_attempts`, `id_sequences` |

---

## 11. Production Folder Structure

> [!IMPORTANT]
> This folder structure is the **canonical reference** for AI-assisted implementation. Every file created must follow this structure exactly. No frameworks — all vanilla PHP, HTML, CSS, and JavaScript.

```
catarman-animal-shelter/
├── .env                              # Environment variables (DB creds, app secrets) — NEVER committed
├── .env.example                      # Template .env with placeholder values — committed
├── .gitignore                        # Git ignore rules
├── composer.json                     # PHP dependencies (QR, PDF, etc.)
├── composer.lock                     # Locked dependency versions
├── README.md                         # Project setup and documentation
│
├── config/                           # Application configuration
│   ├── app.php                       # App-wide constants (APP_NAME, APP_URL, TIMEZONE, etc.)
│   ├── database.php                  # MySQL connection config (reads from .env)
│   ├── auth.php                      # Auth settings (session lifetime, password policy, lockout)
│   ├── cors.php                      # CORS allowed origins, methods, headers
│   ├── rate_limit.php                # Rate limit rules per endpoint category
│   └── mail.php                      # SMTP settings for email notifications
│
├── database/                         # Database management
│   ├── migrations/                   # Versioned SQL migration files
│   │   ├── 001_create_users_table.sql
│   │   ├── 002_create_roles_permissions.sql
│   │   ├── 003_create_animals_table.sql
│   │   ├── 004_create_kennels_table.sql
│   │   ├── 005_create_medical_records.sql
│   │   ├── 006_create_adoption_tables.sql
│   │   ├── 007_create_billing_tables.sql
│   │   ├── 008_create_inventory_tables.sql
│   │   ├── 009_create_audit_logs.sql
│   │   ├── 010_create_notifications.sql
│   │   └── 011_create_indexes.sql
│   ├── seeders/                      # Initial data seeders
│   │   ├── roles_seeder.sql          # Default roles and permissions
│   │   ├── admin_seeder.sql          # Default super admin account
│   │   ├── breeds_seeder.sql         # Breed reference data
│   │   ├── fee_schedule_seeder.sql   # Default fee schedule
│   │   └── kennels_seeder.sql        # Initial kennel configuration
│   ├── backups/                      # Database backup storage (gitignored)
│   └── migrate.php                   # Migration runner script
│
├── public/                           # Web root — server points here
│   ├── index.php                     # Single entry point (front controller)
│   ├── .htaccess                     # Apache rewrite rules (if using Apache)
│   ├── favicon.ico                   # Site favicon
│   ├── robots.txt                    # Search engine directives
│   │
│   ├── assets/                       # Static assets (publicly accessible)
│   │   ├── css/                      # Stylesheets
│   │   │   ├── variables.css         # CSS custom properties (colors, fonts, spacing)
│   │   │   ├── reset.css             # CSS reset / normalize
│   │   │   ├── base.css              # Base typography, global styles
│   │   │   ├── layout.css            # Grid system, containers, responsive utilities
│   │   │   ├── components.css        # Reusable component styles (buttons, cards, modals, forms)
│   │   │   ├── landing.css           # Landing page specific styles
│   │   │   ├── dashboard.css         # Dashboard specific styles
│   │   │   ├── tables.css            # Data table & card-list responsive styles
│   │   │   ├── forms.css             # Form layout and input styles
│   │   │   ├── responsive.css        # Media queries and breakpoint overrides
│   │   │   └── print.css             # Print-specific styles for reports/invoices
│   │   │
│   │   ├── js/                       # JavaScript files
│   │   │   ├── app.js                # App initialization, global event listeners
│   │   │   ├── router.js             # Client-side page navigation (fetch-based SPA-like)
│   │   │   ├── api.js                # Centralized API client (fetch wrapper with auth headers)
│   │   │   ├── auth.js               # Login, logout, session check, password reset
│   │   │   ├── utils.js              # Utility functions (date format, currency, debounce, etc.)
│   │   │   ├── validation.js         # Client-side form validation (mirrors server-side rules)
│   │   │   ├── qr-scanner.js         # QR code scanning logic (html5-qrcode wrapper)
│   │   │   ├── charts.js             # Dashboard chart initialization (Chart.js wrapper)
│   │   │   ├── notifications.js      # In-app notification polling/display
│   │   │   │
│   │   │   └── modules/              # Module-specific JavaScript
│   │   │       ├── animal-intake.js   # Intake form logic, photo upload, QR generation
│   │   │       ├── medical-records.js # Dynamic form switching, form submission
│   │   │       ├── kennel-mgmt.js     # Kennel grid interactions, drag-and-drop
│   │   │       ├── adoption.js        # Adoption pipeline kanban, stage transitions
│   │   │       ├── billing.js         # Invoice generation, payment recording
│   │   │       ├── inventory.js       # Stock transactions, low-stock alerts
│   │   │       ├── user-mgmt.js       # User CRUD, role management
│   │   │       ├── reports.js         # Report generation, export triggers
│   │   │       └── landing.js         # Landing page interactions (gallery, filters)
│   │   │
│   │   ├── images/                   # Static images
│   │   │   ├── logo.png              # Shelter logo
│   │   │   ├── logo-white.png        # White variant for dark backgrounds
│   │   │   ├── favicon/              # Favicon variants (16, 32, 180, 192, 512)
│   │   │   ├── placeholders/         # Default animal photo, avatar, etc.
│   │   │   └── icons/                # UI icons (SVG preferred)
│   │   │
│   │   └── vendor/                   # Third-party JS/CSS libraries (local copies)
│   │       ├── chart.min.js          # Chart.js
│   │       ├── html5-qrcode.min.js   # QR scanner library
│   │       └── signature_pad.min.js  # Digital signature canvas
│   │
│   └── uploads/                      # User-uploaded files (gitignored)
│       ├── animals/                  # Animal photos (organized by animal_id)
│       │   └── A-2026-0001/
│       │       ├── photo_1.jpg
│       │       └── photo_2.jpg
│       ├── documents/                # Adoption documents, ID uploads
│       ├── qrcodes/                  # Generated QR code PNGs
│       ├── invoices/                 # Generated invoice PDFs
│       └── reports/                  # Generated report exports
│
├── src/                              # Application source code (NOT publicly accessible)
│   ├── bootstrap.php                 # App bootstrapper (load .env, autoloader, error handler, session)
│   ├── autoload.php                  # PSR-4 style class autoloader
│   │
│   ├── Core/                         # Framework-like core utilities
│   │   ├── Router.php                # URL router — maps routes to controllers
│   │   ├── Request.php               # HTTP request wrapper (GET, POST, JSON body, files)
│   │   ├── Response.php              # HTTP response helper (JSON, redirect, status codes)
│   │   ├── Database.php              # PDO singleton with prepared statement helpers
│   │   ├── Session.php               # Session management wrapper
│   │   ├── Validator.php             # Server-side input validation engine
│   │   ├── Sanitizer.php             # Input sanitization (XSS, HTML encoding)
│   │   ├── Logger.php                # Structured JSON logger (writes to /logs/)
│   │   ├── Middleware.php            # Base middleware class
│   │   └── View.php                  # Simple PHP template renderer
│   │
│   ├── Middleware/                    # HTTP middleware stack
│   │   ├── AuthMiddleware.php        # Verify active session, reject unauthenticated
│   │   ├── RBACMiddleware.php        # Check role & permission for current route
│   │   ├── CORSMiddleware.php        # Set CORS headers from config
│   │   ├── RateLimitMiddleware.php   # Rate limit enforcement per endpoint category
│   │   ├── CSRFMiddleware.php        # CSRF token validation for form submissions
│   │   └── SanitizationMiddleware.php# Auto-sanitize all incoming request data
│   │
│   ├── Controllers/                  # Request handlers (thin — delegate to Services)
│   │   ├── AuthController.php        # Login, logout, password reset, register
│   │   ├── DashboardController.php   # Dashboard data aggregation API
│   │   ├── AnimalController.php      # Animal CRUD, profile, status updates
│   │   ├── MedicalController.php     # Medical record CRUD (all procedure types)
│   │   ├── KennelController.php      # Kennel CRUD, assignment, map data
│   │   ├── AdoptionController.php    # Adoption pipeline CRUD, stage transitions
│   │   ├── BillingController.php     # Invoice CRUD, payment recording
│   │   ├── InventoryController.php   # Inventory CRUD, stock transactions
│   │   ├── UserController.php        # User management CRUD
│   │   ├── ReportController.php      # Report generation and export
│   │   ├── QRCodeController.php      # QR generation and scan lookup
│   │   └── NotificationController.php# Notification CRUD and polling
│   │
│   ├── Models/                       # Data access objects (one per table)
│   │   ├── User.php                  # users table queries
│   │   ├── Role.php                  # roles + role_permissions queries
│   │   ├── Animal.php                # animals + animal_photos + animal_qr_codes
│   │   ├── Kennel.php                # kennels + kennel_assignments
│   │   ├── MedicalRecord.php         # medical_records + sub-type tables
│   │   ├── VaccinationRecord.php     # vaccination_records
│   │   ├── SurgeryRecord.php         # surgery_records
│   │   ├── ExaminationRecord.php     # examination_records
│   │   ├── TreatmentRecord.php       # treatment_records
│   │   ├── DewormingRecord.php       # deworming_records
│   │   ├── EuthanasiaRecord.php      # euthanasia_records
│   │   ├── AdoptionApplication.php   # adoption_applications
│   │   ├── AdoptionInterview.php     # adoption_interviews
│   │   ├── AdoptionSeminar.php       # adoption_seminars + seminar_attendees
│   │   ├── Invoice.php               # invoices + invoice_line_items
│   │   ├── Payment.php               # payments
│   │   ├── InventoryItem.php         # inventory_items
│   │   ├── StockTransaction.php      # stock_transactions
│   │   ├── AuditLog.php              # audit_logs (append-only)
│   │   ├── Notification.php          # notifications
│   │   └── FeeSchedule.php           # fee_schedule
│   │
│   ├── Services/                     # Business logic layer
│   │   ├── AuthService.php           # Authentication, password hashing, session management
│   │   ├── AnimalService.php         # Animal intake logic, status transitions
│   │   ├── QRCodeService.php         # QR code generation and scanning
│   │   ├── MedicalService.php        # Medical record creation, inventory auto-deduction
│   │   ├── KennelService.php         # Kennel assignment, availability calculation
│   │   ├── AdoptionService.php       # Adoption pipeline stage management
│   │   ├── BillingService.php        # Invoice generation, payment processing
│   │   ├── InventoryService.php      # Stock management, low-stock alerts
│   │   ├── ReportService.php         # Report data aggregation, PDF/CSV generation
│   │   ├── NotificationService.php   # Notification creation and delivery
│   │   ├── BackupService.php         # Database backup and restore logic
│   │   ├── AuditService.php          # Audit log recording
│   │   └── MailService.php           # Email sending (password reset, notifications)
│   │
│   ├── Helpers/                      # Standalone utility functions
│   │   ├── date_helpers.php          # Date formatting, timezone conversion
│   │   ├── string_helpers.php        # String manipulation, slug generation
│   │   ├── file_helpers.php          # File upload handling, path sanitization
│   │   ├── id_generator.php          # Generate formatted IDs (A-YYYY-NNNN, INV-YYYY-NNNN)
│   │   └── response_helpers.php      # JSON response shortcuts, error formatters
│   │
│   └── Views/                        # PHP template files (.php HTML templates)
│       ├── layouts/                  # Base page layouts
│       │   ├── app.php               # Main authenticated layout (sidebar + content area)
│       │   ├── public.php            # Public layout (landing page, adopter pages)
│       │   ├── auth.php              # Auth pages layout (login, register, reset)
│       │   └── error.php             # Error page layout (404, 403, 500)
│       │
│       ├── components/               # Reusable partial templates
│       │   ├── header.php            # Top navigation bar
│       │   ├── sidebar.php           # Admin sidebar navigation
│       │   ├── footer.php            # Page footer
│       │   ├── mobile-nav.php        # Mobile bottom navigation bar
│       │   ├── modal.php             # Generic modal template
│       │   ├── alert.php             # Flash message / toast notification
│       │   ├── pagination.php        # Pagination controls
│       │   ├── data-table.php        # Responsive data table component
│       │   ├── file-upload.php       # Drag-and-drop file upload component
│       │   ├── qr-scanner-modal.php  # QR scanner overlay modal
│       │   └── breadcrumb.php        # Navigation breadcrumbs
│       │
│       ├── pages/                    # Full page templates
│       │   ├── landing/
│       │   │   ├── index.php         # Landing page (hero, gallery, process, about)
│       │   │   ├── animal-detail.php # Public animal detail view
│       │   │   └── adopt-apply.php   # Adoption application form
│       │   │
│       │   ├── auth/
│       │   │   ├── login.php         # Login page
│       │   │   ├── register.php      # Adopter registration
│       │   │   ├── forgot-password.php
│       │   │   └── reset-password.php
│       │   │
│       │   ├── dashboard/
│       │   │   └── index.php         # Dashboard page (metrics, charts, feed)
│       │   │
│       │   ├── animals/
│       │   │   ├── index.php         # Animal list (table/card view)
│       │   │   ├── create.php        # New intake form
│       │   │   ├── show.php          # Animal profile page
│       │   │   └── edit.php          # Edit animal info
│       │   │
│       │   ├── medical/
│       │   │   ├── index.php         # Medical records list
│       │   │   ├── create.php        # New record — procedure type selector
│       │   │   ├── vaccination.php   # Vaccination form
│       │   │   ├── surgery.php       # Surgery form
│       │   │   ├── examination.php   # General examination form
│       │   │   ├── treatment.php     # Treatment/medication form
│       │   │   ├── deworming.php     # Deworming form
│       │   │   ├── euthanasia.php    # Euthanasia form
│       │   │   └── timeline.php      # Medical history timeline view
│       │   │
│       │   ├── kennels/
│       │   │   ├── index.php         # Kennel map / grid view
│       │   │   ├── detail.php        # Single kennel detail
│       │   │   └── manage.php        # Kennel configuration (admin)
│       │   │
│       │   ├── adoptions/
│       │   │   ├── index.php         # Adoption pipeline kanban
│       │   │   ├── application.php   # Application review form
│       │   │   ├── interview.php     # Interview scheduling & results
│       │   │   ├── seminars.php      # Seminar management
│       │   │   └── completion.php    # Adoption completion checklist
│       │   │
│       │   ├── billing/
│       │   │   ├── index.php         # Invoice list / billing dashboard
│       │   │   ├── create.php        # Create/edit invoice
│       │   │   ├── show.php          # Invoice detail / printable view
│       │   │   └── payment.php       # Record payment form
│       │   │
│       │   ├── inventory/
│       │   │   ├── index.php         # Inventory list
│       │   │   ├── create.php        # Add new item
│       │   │   ├── transactions.php  # Stock in/out/adjust
│       │   │   └── alerts.php        # Low-stock and expiry alerts
│       │   │
│       │   ├── users/
│       │   │   ├── index.php         # User list
│       │   │   ├── create.php        # Create user form
│       │   │   ├── show.php          # User profile / activity
│       │   │   └── roles.php         # Role & permission management
│       │   │
│       │   ├── reports/
│       │   │   ├── index.php         # Report selector / builder
│       │   │   └── view.php          # Report results display
│       │   │
│       │   └── errors/
│       │       ├── 403.php           # Forbidden
│       │       ├── 404.php           # Not Found
│       │       ├── 429.php           # Too Many Requests
│       │       └── 500.php           # Server Error
│       │
│       └── emails/                   # Email templates (HTML)
│           ├── password-reset.php
│           ├── adoption-status.php
│           ├── interview-schedule.php
│           ├── seminar-invite.php
│           └── invoice-receipt.php
│
├── routes/                           # Route definitions
│   ├── web.php                       # Page routes (return HTML views)
│   ├── api.php                       # API routes (return JSON)
│   └── middleware.php                # Route-to-middleware mappings
│
├── logs/                             # Application log files (gitignored)
│   ├── app-2026-03-23.log            # Daily rotating log files
│   ├── error-2026-03-23.log          # Error-only log
│   └── audit-2026-03-23.log          # Audit trail log
│
├── storage/                          # Non-public file storage (gitignored)
│   ├── cache/                        # File-based cache
│   ├── sessions/                     # PHP session files
│   └── temp/                         # Temporary processing files
│
├── tests/                            # Test files
│   ├── Unit/                         # Unit tests (models, services, helpers)
│   ├── Integration/                  # Integration tests (API endpoints)
│   └── test_bootstrap.php            # Test environment setup
│
├── scripts/                          # CLI utility scripts
│   ├── serve.sh                      # Start PHP dev server: php -S localhost:8000 -t public/
│   ├── serve.bat                     # Windows equivalent
│   ├── migrate.php                   # Run pending migrations
│   ├── seed.php                      # Run database seeders
│   ├── backup.php                    # Create database backup
│   ├── restore.php                   # Restore from backup
│   └── create_admin.php              # Create initial super admin account
│
├── docker/                           # Docker configuration (production)
│   ├── Dockerfile                    # PHP-FPM container
│   ├── nginx.conf                    # Nginx site configuration
│   └── docker-compose.yml            # Full stack: Nginx + PHP-FPM + MySQL
│
└── docs/                             # Project documentation
    ├── PRD.md                        # This document
    ├── API.md                        # API endpoint documentation
    ├── DATABASE.md                   # Full database schema documentation
    └── SETUP.md                      # Development environment setup guide
```

### Folder Structure Key Rules for AI Implementation

1. **`public/index.php` is the single entry point** — all HTTP requests are routed through this file via `.htaccess` or PHP built-in server router
2. **`src/` is never publicly accessible** — all PHP logic lives here, protected from direct URL access
3. **`uploads/` is within `public/`** but gitignored — uploaded files are web-accessible but not version-controlled
4. **Controllers are thin** — they validate input, call a Service, and return a Response. No business logic in controllers.
5. **Models handle only database queries** — raw PDO prepared statements, no ORM. Each model maps to one primary table.
6. **Services contain business logic** — complex operations spanning multiple models go here.
7. **Views are plain PHP templates** — use `<?php include ?>` for partials. No template engine.
8. **Every medical procedure type gets its own view file** — not a single dynamic form (per PRD spec for "separate pages").
9. **CSS follows component methodology** — `variables.css` → `reset.css` → `base.css` → `layout.css` → `components.css` → page-specific → `responsive.css`
10. **JavaScript is modular** — `app.js` initializes, `api.js` handles all HTTP calls, module-specific files handle page logic

---

## 12. Deployment & Rollback Strategy

### Environments

| Environment | Purpose                            | URL Pattern                          |
| ----------- | ---------------------------------- | ------------------------------------ |
| Development | Local developer machines           | `localhost:3000` / `localhost:8000`  |
| Staging     | Pre-production testing             | `staging.shelter.catarman.gov`       |
| Production  | Live system                        | `shelter.catarman.gov`               |

### CI/CD Pipeline

```
Code Push → Lint & Format Check → Unit Tests → Build →
Deploy to Staging → Integration Tests → Manual QA Sign-off →
Blue-Green Deploy to Production → Smoke Tests → Monitor
```

### Backup Schedule

| Backup Type    | Frequency | Retention | Storage              |
| -------------- | --------- | --------- | -------------------- |
| Full DB Backup | Daily     | 30 days   | Encrypted off-site   |
| Incremental    | Every 6hr | 7 days    | Encrypted off-site   |
| File Storage   | Daily     | 30 days   | Separate backup      |
| Audit Logs     | Archived  | 1 year    | Cold storage         |

---

## 13. Supplementary Documents (AI Build References)

> [!IMPORTANT]
> These documents complete the PRD and are **required reading** for any AI agent building this system. The PRD defines *what* to build — these documents define *how*.

| Document | File | Purpose |
|----------|------|---------|
| **API Route Definitions** | [API_ROUTES.md](file:///c:/Users/TESS%20LARON/Desktop/REVISED/API_ROUTES.md) | 120+ endpoints with HTTP methods, controllers, middleware, query parameters, JSON response format, error codes, and router bootstrap |
| **Page Layout Specs** | [PAGE_LAYOUTS.md](file:///c:/Users/TESS%20LARON/Desktop/REVISED/PAGE_LAYOUTS.md) | ASCII wireframes for all 12 major pages with exact component placement, responsive breakpoint notes, and interaction behaviors |
| **Validation Rules** | [VALIDATION_RULES.md](file:///c:/Users/TESS%20LARON/Desktop/REVISED/VALIDATION_RULES.md) | Field-level validation rules for every API endpoint, custom rule reference, validator usage pattern, and sanitization rules |
| **Database Schema** | [database_schema.sql](file:///c:/Users/TESS%20LARON/Desktop/REVISED/database_schema.sql) | Full 38-table SQL migration with constraints, indexes, naming conventions — ready to execute |
| **Seeder Data** | [seeders.sql](file:///c:/Users/TESS%20LARON/Desktop/REVISED/seeders.sql) | Bootstrap data: 6 roles, 34 permissions, role-permission mappings, admin user, 27 breeds, 5 inventory categories, 16 fee items, 20 kennels, ID sequences, 6 report templates |
| **Implementation Guide** | [IMPLEMENTATION_GUIDE.md](file:///c:/Users/TESS%20LARON/Desktop/REVISED/IMPLEMENTATION_GUIDE.md) | **START HERE** — 14-phase sequential build order with specific files per phase, verification checkpoints, dependency graph, cross-module integration tests, and security checklist |

### AI Build Order

> [!IMPORTANT]
> Follow the **[Implementation Guide](file:///c:/Users/TESS%20LARON/Desktop/REVISED/IMPLEMENTATION_GUIDE.md)** for the full phase-by-phase build instructions with verification checkpoints. Summary below:

```
Phase 0:  Environment Setup (PHP, MySQL, Composer, schema, seeders)
Phase 1:  Project Skeleton & Core Classes (Router, DB, Validator, Logger)
Phase 2:  Middleware & Authentication (Login, Sessions, RBAC, Rate Limiting)
Phase 3:  Layout Shell & Design System (CSS tokens, theme toggle, toasts)
Phase 4:  Dashboard (Stats, Charts, Activity Feed)
Phase 5:  Animal Module (CRUD, QR, Photos, Status Transitions)
Phase 6:  Kennel Module (Grid View, Assignment, Maintenance)
Phase 7:  Medical Records (Dynamic Forms, Sub-type Tables)
Phase 8:  Adoption Module (Pipeline, Portal, Certificates)
Phase 9:  Billing Module (Invoices, Payments, Fee Schedule, PDF)
Phase 10: Inventory Module (Stock, Transactions, Alerts)
Phase 11: User Management & Reports (CRUD, Export, Notifications)
Phase 12: System & Production Hardening (Backups, Error Pages, Security)
Phase 13: Integration Testing & Final QA
```

---

## 14. Appendices

### Appendix A: Glossary

| Term           | Definition                                                    |
| -------------- | ------------------------------------------------------------- |
| RBAC           | Role-Based Access Control                                     |
| CORS           | Cross-Origin Resource Sharing                                 |
| QR Code        | Quick Response Code — 2D barcode for fast data retrieval      |
| Soft Delete    | Marking records as deleted without removing from database     |
| Blue-Green     | Deployment strategy using two identical environments          |
| BCS            | Body Condition Score (1-9 scale for animal health assessment) |
| DHPP           | Distemper, Hepatitis, Parainfluenza, Parvovirus vaccine       |
| FVRCP          | Feline Viral Rhinotracheitis, Calicivirus, Panleukopenia     |
| SPA            | Single Page Application                                       |

### Appendix B: Compliance & Legal
- Local Government Unit (LGU) data privacy compliance
- Republic Act No. 10173 - Data Privacy Act of 2012
- Republic Act No. 8485 - Animal Welfare Act of 1998
- Municipal ordinances related to animal control and impounding

### Appendix C: Future Enhancements (Out of Scope for v1.0)
- Mobile application (React Native / Flutter)
- Foster care program management
- Volunteer management module
- Donation tracking and management
- Public reporting / statistics page
- Integration with national pet registration database
- AI-powered breed identification from photos
- Automated lost-and-found pet matching
- SMS-based notifications (GCash integration for payments)

---

> **Document Status:** This PRD is a living document. All changes must be versioned and approved by the project stakeholders before implementation.

---

*End of PRD — Catarman Dog Pound & Animal Shelter Management System v1.0*
