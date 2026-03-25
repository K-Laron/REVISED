-- ============================================================================
-- Catarman Dog Pound & Animal Shelter — Seeder Data
-- Run AFTER database_schema.sql
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- 1. ROLES
-- ============================================================================
INSERT INTO `roles` (`id`, `name`, `display_name`, `description`, `is_system`) VALUES
(1, 'super_admin',    'Super Administrator', 'Full system access. Cannot be deleted.', 1),
(2, 'shelter_head',   'Shelter Head',        'Shelter director with dashboard and reports access.', 1),
(3, 'veterinarian',   'Veterinarian',        'Creates and manages medical records.', 1),
(4, 'shelter_staff',  'Shelter Staff',       'Day-to-day operations: intake, kennel, inventory.', 1),
(5, 'billing_clerk',  'Billing Clerk',       'Manages invoices, payments, and fee schedules.', 1),
(6, 'adopter',        'Adopter',             'Public user who applies to adopt animals.', 1);

-- ============================================================================
-- 2. PERMISSIONS (module.action format)
-- ============================================================================
INSERT INTO `permissions` (`name`, `display_name`, `module`) VALUES
-- Animals
('animals.create',  'Create Animals',    'animals'),
('animals.read',    'View Animals',      'animals'),
('animals.update',  'Edit Animals',      'animals'),
('animals.delete',  'Delete Animals',    'animals'),
-- Medical
('medical.create',  'Create Medical Records', 'medical'),
('medical.read',    'View Medical Records',   'medical'),
('medical.update',  'Edit Medical Records',   'medical'),
('medical.delete',  'Delete Medical Records', 'medical'),
-- Kennels
('kennels.create',  'Create Kennels',    'kennels'),
('kennels.read',    'View Kennels',      'kennels'),
('kennels.update',  'Edit Kennels',      'kennels'),
('kennels.delete',  'Delete Kennels',    'kennels'),
-- Adoptions
('adoptions.create', 'Create Adoptions',  'adoptions'),
('adoptions.read',   'View Adoptions',    'adoptions'),
('adoptions.update', 'Manage Adoptions',  'adoptions'),
('adoptions.delete', 'Delete Adoptions',  'adoptions'),
-- Billing
('billing.create',  'Create Invoices',   'billing'),
('billing.read',    'View Billing',      'billing'),
('billing.update',  'Edit Billing',      'billing'),
('billing.delete',  'Void Invoices',     'billing'),
-- Inventory
('inventory.create', 'Create Inventory Items', 'inventory'),
('inventory.read',   'View Inventory',         'inventory'),
('inventory.update', 'Manage Inventory',       'inventory'),
('inventory.delete', 'Delete Inventory Items', 'inventory'),
-- Users
('users.create',    'Create Users',      'users'),
('users.read',      'View Users',        'users'),
('users.update',    'Edit Users',        'users'),
('users.delete',    'Delete Users',      'users'),
-- Reports
('reports.read',    'View Reports',      'reports'),
('reports.export',  'Export Reports',    'reports'),
('reports.create',  'Save Report Templates', 'reports'),
-- Settings
('settings.read',   'View Settings',     'settings'),
('settings.update', 'Edit Settings',     'settings');

-- ============================================================================
-- 3. ROLE-PERMISSION MAPPING
-- ============================================================================

-- Super Admin: ALL permissions
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`;

-- Shelter Head: All read + reports + adoptions manage
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 2, id FROM `permissions`
WHERE `name` IN (
  'animals.read', 'animals.create', 'animals.update',
  'medical.read',
  'kennels.read',
  'adoptions.read', 'adoptions.update',
  'billing.read',
  'inventory.read',
  'users.read',
  'reports.read', 'reports.export', 'reports.create',
  'settings.read'
);

-- Veterinarian: Animals read + Medical full + Inventory read
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 3, id FROM `permissions`
WHERE `name` IN (
  'animals.read', 'animals.update',
  'medical.create', 'medical.read', 'medical.update', 'medical.delete',
  'kennels.read',
  'inventory.read', 'inventory.update',
  'reports.read'
);

-- Shelter Staff: Animals, Kennels, Inventory, Adoptions read
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 4, id FROM `permissions`
WHERE `name` IN (
  'animals.create', 'animals.read', 'animals.update',
  'medical.read',
  'kennels.create', 'kennels.read', 'kennels.update',
  'adoptions.read', 'adoptions.update',
  'inventory.create', 'inventory.read', 'inventory.update',
  'reports.read'
);

-- Billing Clerk: Billing full + Adoptions read + Animals read
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 5, id FROM `permissions`
WHERE `name` IN (
  'animals.read',
  'adoptions.read',
  'billing.create', 'billing.read', 'billing.update', 'billing.delete',
  'reports.read', 'reports.export'
);

-- Adopter: No admin permissions (uses portal routes only)
-- No entries needed

-- ============================================================================
-- 4. DEFAULT ADMIN USER (password: ChangeMe@2025)
-- ============================================================================
INSERT INTO `users` (`id`, `role_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `is_active`, `email_verified_at`, `force_password_change`) VALUES
(1, 1, 'super_admin-0001', 'admin@catarmanshelter.gov.ph', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'System', 'Administrator', 1, NOW(), 1);
-- Password: ChangeMe@2025

-- ============================================================================
-- 5. BREEDS (Common Philippine dog/cat breeds)
-- ============================================================================
INSERT INTO `breeds` (`species`, `name`) VALUES
-- Dogs
('Dog', 'Aspin (Asong Pinoy)'),
('Dog', 'Labrador Retriever'),
('Dog', 'Golden Retriever'),
('Dog', 'Siberian Husky'),
('Dog', 'German Shepherd'),
('Dog', 'Shih Tzu'),
('Dog', 'Pomeranian'),
('Dog', 'Chihuahua'),
('Dog', 'Beagle'),
('Dog', 'Poodle'),
('Dog', 'Dachshund'),
('Dog', 'Bulldog'),
('Dog', 'Pit Bull'),
('Dog', 'Rottweiler'),
('Dog', 'Doberman'),
('Dog', 'Mixed Breed'),
('Dog', 'Unknown'),
-- Cats
('Cat', 'Puspin (Pusang Pinoy)'),
('Cat', 'Persian'),
('Cat', 'Siamese'),
('Cat', 'Maine Coon'),
('Cat', 'British Shorthair'),
('Cat', 'Ragdoll'),
('Cat', 'Bengal'),
('Cat', 'Scottish Fold'),
('Cat', 'Mixed Breed'),
('Cat', 'Unknown');

-- ============================================================================
-- 6. INVENTORY CATEGORIES
-- ============================================================================
INSERT INTO `inventory_categories` (`name`, `description`) VALUES
('Medical Supplies',    'Vaccines, syringes, medications, surgical supplies'),
('Food & Nutrition',    'Dog food, cat food, supplements, treats'),
('Cleaning Supplies',   'Disinfectants, soaps, cleaning tools'),
('Office Supplies',     'Paper, ink, folders, forms'),
('Equipment',           'Cages, leashes, grooming tools, medical equipment');

-- ============================================================================
-- 7. FEE SCHEDULE (Default fees)
-- ============================================================================
INSERT INTO `fee_schedule` (`category`, `name`, `description`, `amount`, `is_per_day`, `species_filter`, `effective_from`, `is_active`, `created_by`) VALUES
('Adoption',   'Dog Adoption Fee',           'Standard adoption fee for dogs',                500.00, 0, 'Dog',  '2025-01-01', 1, 1),
('Adoption',   'Cat Adoption Fee',           'Standard adoption fee for cats',                300.00, 0, 'Cat',  '2025-01-01', 1, 1),
('Adoption',   'Puppy/Kitten Adoption Fee',  'Adoption fee for animals under 6 months',       400.00, 0, NULL,   '2025-01-01', 1, 1),
('Surrender',  'Owner Surrender Fee',        'Fee for voluntarily surrendering an animal',     200.00, 0, NULL,   '2025-01-01', 1, 1),
('Impound',    'Daily Impound Fee',          'Daily boarding fee for impounded animals',        50.00, 1, NULL,   '2025-01-01', 1, 1),
('Impound',    'Impound Release Fee',        'One-time fee to release impounded animal',      300.00, 0, NULL,   '2025-01-01', 1, 1),
('Medical',    'Anti-Rabies Vaccination',    'Anti-rabies vaccine administration',             150.00, 0, NULL,   '2025-01-01', 1, 1),
('Medical',    '5-in-1 Vaccination (Dog)',   'DHPP vaccine for dogs',                          350.00, 0, 'Dog',  '2025-01-01', 1, 1),
('Medical',    '4-in-1 Vaccination (Cat)',   'FVRCP vaccine for cats',                         300.00, 0, 'Cat',  '2025-01-01', 1, 1),
('Medical',    'Spay/Neuter (Dog)',          'Spay or neuter surgery for dogs',               1500.00, 0, 'Dog',  '2025-01-01', 1, 1),
('Medical',    'Spay/Neuter (Cat)',          'Spay or neuter surgery for cats',               1000.00, 0, 'Cat',  '2025-01-01', 1, 1),
('Medical',    'General Consultation',       'Veterinary examination',                         200.00, 0, NULL,   '2025-01-01', 1, 1),
('Medical',    'Deworming',                  'Deworming treatment',                            100.00, 0, NULL,   '2025-01-01', 1, 1),
('License',    'Pet License (Annual)',       'Annual pet licensing fee',                       100.00, 0, NULL,   '2025-01-01', 1, 1),
('Fine',       'Leash Law Violation',        'Fine for unleashed pet in public area',          500.00, 0, NULL,   '2025-01-01', 1, 1);

-- ============================================================================
-- 8. DEFAULT KENNELS
-- ============================================================================
INSERT INTO `kennels` (`kennel_code`, `zone`, `row_number`, `size_category`, `type`, `allowed_species`, `max_occupants`, `status`, `created_by`) VALUES
-- Building A (Small/Medium dogs)
('K-A01', 'Building A', '1', 'Small',  'Indoor', 'Dog', 1, 'Available', 1),
('K-A02', 'Building A', '1', 'Small',  'Indoor', 'Dog', 1, 'Available', 1),
('K-A03', 'Building A', '1', 'Medium', 'Indoor', 'Dog', 1, 'Available', 1),
('K-A04', 'Building A', '2', 'Medium', 'Indoor', 'Dog', 1, 'Available', 1),
('K-A05', 'Building A', '2', 'Medium', 'Indoor', 'Dog', 1, 'Available', 1),
('K-A06', 'Building A', '2', 'Small',  'Indoor', 'Dog', 1, 'Available', 1),
-- Building B (Large dogs)
('K-B01', 'Building B', '1', 'Large',       'Indoor', 'Dog', 1, 'Available', 1),
('K-B02', 'Building B', '1', 'Large',       'Indoor', 'Dog', 1, 'Available', 1),
('K-B03', 'Building B', '1', 'Extra Large', 'Indoor', 'Dog', 2, 'Available', 1),
('K-B04', 'Building B', '2', 'Large',       'Indoor', 'Dog', 1, 'Available', 1),
('K-B05', 'Building B', '2', 'Large',       'Indoor', 'Dog', 1, 'Available', 1),
-- Building C (Cats)
('K-C01', 'Building C', '1', 'Small',  'Indoor', 'Cat', 2, 'Available', 1),
('K-C02', 'Building C', '1', 'Small',  'Indoor', 'Cat', 2, 'Available', 1),
('K-C03', 'Building C', '1', 'Medium', 'Indoor', 'Cat', 3, 'Available', 1),
('K-C04', 'Building C', '2', 'Small',  'Indoor', 'Cat', 2, 'Available', 1),
-- Outdoor Runs
('K-O01', 'Outdoor Area', '1', 'Extra Large', 'Outdoor', 'Dog', 3, 'Available', 1),
('K-O02', 'Outdoor Area', '1', 'Extra Large', 'Outdoor', 'Dog', 3, 'Available', 1),
-- Quarantine / Isolation
('K-Q01', 'Quarantine', '1', 'Medium', 'Indoor', 'Any', 1, 'Available', 1),
('K-Q02', 'Quarantine', '1', 'Medium', 'Indoor', 'Any', 1, 'Available', 1),
('K-Q03', 'Quarantine', '1', 'Large',  'Indoor', 'Any', 1, 'Available', 1);

-- ============================================================================
-- 9. ID SEQUENCES (Initialize for year 2025)
-- ============================================================================
INSERT INTO `id_sequences` (`sequence_key`, `prefix`, `current_year`, `last_number`) VALUES
('animal_id',         'A',   2025, 0),
('invoice_number',    'INV', 2025, 0),
('application_number','APP', 2025, 0),
('payment_number',    'PAY', 2025, 0);

-- ============================================================================
-- 10. REPORT TEMPLATES (Built-in)
-- ============================================================================
INSERT INTO `report_templates` (`name`, `report_type`, `configuration`, `is_system`) VALUES
('Monthly Intake Summary',       'intake',        '{"group_by":"month","columns":["species","intake_type","condition_at_intake","count"],"date_range":"current_month"}', 1),
('Vaccination Schedule',         'medical',       '{"filter":"vaccination","columns":["animal_name","vaccine_name","date","next_due_date","vet"],"sort":"next_due_date"}', 1),
('Adoption Pipeline Status',     'adoptions',     '{"group_by":"status","columns":["application_number","adopter","animal","status","days_in_stage"],"sort":"created_at"}', 1),
('Monthly Revenue Report',       'billing',       '{"group_by":"month","columns":["category","count","total_amount","paid","outstanding"],"date_range":"current_month"}', 1),
('Inventory Stock Alert',        'inventory',     '{"filter":"low_stock_or_expiring","columns":["sku","name","category","quantity","reorder_level","expiry_date"],"sort":"quantity"}', 1),
('Daily Animal Census',          'census',        '{"columns":["species","status","count","kennels_used","kennels_available"],"grouping":"species,status"}', 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEEDER SUMMARY
-- ============================================================================
-- Roles:               6 (super_admin, shelter_head, vet, staff, clerk, adopter)
-- Permissions:         34 (across 9 modules)
-- Role-Permissions:    Mapped for all 5 staff roles
-- Default Admin:       1 (force password change on first login)
-- Breeds:              27 (17 dogs + 10 cats, Philippine-relevant)
-- Inventory Categories: 5
-- Fee Schedule:        16 items (adoption, surrender, impound, medical, etc.)
-- Kennels:             20 (across 4 zones + quarantine)
-- ID Sequences:        4 (animal, invoice, application, payment)
-- Report Templates:    6 built-in
-- ============================================================================
