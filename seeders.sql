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
-- 9. ID SEQUENCES (Initialize for seeded 2026 data)
-- ============================================================================
INSERT INTO `id_sequences` (`sequence_key`, `prefix`, `current_year`, `last_number`) VALUES
('animal_id',         'A',   2026, 12),
('invoice_number',    'INV', 2026, 5),
('application_number','APP', 2026, 6),
('payment_number',    'PAY', 2026, 4);

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

-- ============================================================================
-- 11. STAFF AND PORTAL USERS
-- ============================================================================
INSERT INTO `users`
(`id`, `role_id`, `username`, `email`, `password_hash`, `first_name`, `last_name`, `middle_name`, `phone`, `address_line1`, `city`, `province`, `zip_code`, `is_active`, `email_verified_at`, `force_password_change`, `created_by`, `updated_by`) VALUES
(2, 2, 'shelter_head-0002', 'maria.dela.cruz@catarmanshelter.gov.ph', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Maria', 'Dela Cruz', 'Santos', '09171230002', 'Municipal Hall Compound, Catarman', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(3, 3, 'veterinarian-0003', 'luis.ramos@catarmanshelter.gov.ph', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Luis', 'Ramos', 'Mendoza', '09171230003', 'Veterinary Office, Catarman', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(4, 3, 'veterinarian-0004', 'ana.flores@catarmanshelter.gov.ph', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Ana', 'Flores', 'Reyes', '09171230004', 'Veterinary Office Annex, Catarman', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(5, 4, 'shelter_staff-0005', 'jose.tan@catarmanshelter.gov.ph', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Jose', 'Tan', NULL, '09171230005', 'Barangay Dalakit Operations Hub', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(6, 4, 'shelter_staff-0006', 'bea.uy@catarmanshelter.gov.ph', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Bea', 'Uy', NULL, '09171230006', 'Animal Care Center, Catarman', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(7, 5, 'billing_clerk-0007', 'carlo.navarro@catarmanshelter.gov.ph', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Carlo', 'Navarro', NULL, '09171230007', 'Treasury Desk, Catarman Shelter', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(8, 6, 'adopter-0008', 'sophia.mercado@example.com', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Sophia', 'Mercado', NULL, '09181230008', 'Purok 2, Barangay Macagtas', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(9, 6, 'adopter-0009', 'daniel.fernandez@example.com', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Daniel', 'Fernandez', NULL, '09181230009', 'Zone 4, Barangay Narra', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(10, 6, 'adopter-0010', 'elaine.garcia@example.com', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Elaine', 'Garcia', NULL, '09181230010', 'Rosal Street, Barangay Molave', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(11, 6, 'adopter-0011', 'mark.salvador@example.com', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Mark', 'Salvador', NULL, '09181230011', 'Rizal Avenue, Barangay Jose Abad Santos', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1),
(12, 4, 'shelter_staff-0012', 'ivy.lopez@catarmanshelter.gov.ph', '$2y$12$GIZN4Y.l7ltNCVRcA.qEnuY6BS2sSjtwnGnGtz0s3N5Py5jEGSXrm', 'Ivy', 'Lopez', NULL, '09171230012', 'Kennel Operations, Catarman Shelter', 'Catarman', 'Northern Samar', '6400', 1, NOW(), 0, 1, 1);

-- ============================================================================
-- 12. SYSTEM SETTINGS
-- ============================================================================
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('app_name', '"CDP&ASMS"'),
('organization_name', '"Catarman Dog Pound & Animal Shelter Management System"'),
('public_portal_enabled', 'true'),
('contact_email', '"catarmanshelter.gov.ph@example.com"'),
('contact_phone', '"(055) 500-2418"'),
('office_address', '"Municipal Veterinary Office, Catarman, Northern Samar"'),
('mail_delivery_mode', '"log_only"'),
('maintenance_mode_enabled', 'false'),
('maintenance_message', '"The system is undergoing scheduled maintenance. Please check back shortly."');

-- ============================================================================
-- 13. INVENTORY ITEMS
-- ============================================================================
INSERT INTO `inventory_items`
(`id`, `sku`, `name`, `category_id`, `unit_of_measure`, `cost_per_unit`, `supplier_name`, `supplier_contact`, `reorder_level`, `quantity_on_hand`, `storage_location`, `expiry_date`, `is_active`, `is_deleted`, `created_by`, `updated_by`) VALUES
(1, 'MED-ARV-001', 'Anti-Rabies Vaccine', 1, 'vial', 120.00, 'VetSupply Samar', '09170001001', 12, 28, 'Medical Shelf A1', '2027-02-28', 1, 0, 3, 3),
(2, 'MED-DHPP-001', 'DHPP 5-in-1 Vaccine', 1, 'vial', 240.00, 'VetSupply Samar', '09170001001', 10, 18, 'Medical Shelf A2', '2027-03-31', 1, 0, 3, 3),
(3, 'MED-FVRCP-001', 'FVRCP 4-in-1 Vaccine', 1, 'vial', 210.00, 'VetSupply Samar', '09170001001', 8, 14, 'Medical Shelf A3', '2027-03-15', 1, 0, 4, 4),
(4, 'MED-AMOX-250', 'Amoxicillin 250mg', 1, 'capsule', 9.50, 'North Vet Pharmacy', '09170001002', 30, 110, 'Pharmacy Drawer B1', NULL, 1, 0, 3, 3),
(5, 'MED-DEW-001', 'Pyrantel Dewormer', 1, 'tablet', 14.00, 'North Vet Pharmacy', '09170001002', 20, 68, 'Pharmacy Drawer B2', '2027-01-15', 1, 0, 4, 4),
(6, 'FOOD-DOG-001', 'Adult Dog Kibble 20kg', 2, 'bag', 1450.00, 'Paw Pantry Catarman', '09170001003', 6, 24, 'Warehouse Bay 1', '2026-12-31', 1, 0, 5, 5),
(7, 'FOOD-CAT-001', 'Adult Cat Food 10kg', 2, 'bag', 980.00, 'Paw Pantry Catarman', '09170001003', 5, 15, 'Warehouse Bay 2', '2026-11-30', 1, 0, 5, 5),
(8, 'CLN-DIS-001', 'Disinfectant Concentrate', 3, 'bottle', 320.00, 'CleanWorks Samar', '09170001004', 8, 17, 'Janitorial Closet', '2027-05-30', 1, 0, 6, 6),
(9, 'EQP-LEASH-001', 'Slip Leash', 5, 'pcs', 150.00, 'Animal Gear Depot', '09170001005', 10, 22, 'Equipment Rack', NULL, 1, 0, 6, 6),
(10, 'MED-SYR-003', '3ml Syringe', 1, 'box', 210.00, 'VetSupply Samar', '09170001001', 12, 9, 'Medical Shelf C1', '2028-01-31', 1, 0, 3, 3);

INSERT INTO `stock_transactions`
(`id`, `inventory_item_id`, `transaction_type`, `quantity`, `quantity_before`, `quantity_after`, `reason`, `reference_type`, `reference_id`, `batch_lot_number`, `expiry_date`, `source_supplier`, `notes`, `transacted_by`, `transacted_at`) VALUES
(1, 1, 'stock_in', 30, 0, 30, 'purchase', 'manual', NULL, 'ARV-2026-01', '2027-02-28', 'VetSupply Samar', 'Initial anti-rabies stock for Q2 operations.', 3, '2026-03-01 09:00:00'),
(2, 2, 'stock_in', 20, 0, 20, 'purchase', 'manual', NULL, 'DHPP-2026-04', '2027-03-31', 'VetSupply Samar', 'Initial DHPP stock.', 3, '2026-03-01 09:10:00'),
(3, 3, 'stock_in', 15, 0, 15, 'purchase', 'manual', NULL, 'FVRCP-2026-02', '2027-03-15', 'VetSupply Samar', 'Initial feline vaccine stock.', 4, '2026-03-01 09:20:00'),
(4, 4, 'stock_in', 120, 0, 120, 'purchase', 'manual', NULL, NULL, NULL, 'North Vet Pharmacy', 'Initial antibiotic stock.', 3, '2026-03-01 09:30:00'),
(5, 5, 'stock_in', 70, 0, 70, 'purchase', 'manual', NULL, 'DEW-2026-03', '2027-01-15', 'North Vet Pharmacy', 'Initial deworming tablets.', 4, '2026-03-01 09:40:00'),
(6, 6, 'stock_in', 24, 0, 24, 'purchase', 'manual', NULL, NULL, '2026-12-31', 'Paw Pantry Catarman', 'Dog food restock.', 5, '2026-03-02 08:00:00'),
(7, 7, 'stock_in', 15, 0, 15, 'purchase', 'manual', NULL, NULL, '2026-11-30', 'Paw Pantry Catarman', 'Cat food restock.', 5, '2026-03-02 08:10:00'),
(8, 8, 'stock_in', 18, 0, 18, 'purchase', 'manual', NULL, 'DIS-2026-01', '2027-05-30', 'CleanWorks Samar', 'Cleaning chemicals restock.', 6, '2026-03-02 08:20:00'),
(9, 9, 'stock_in', 25, 0, 25, 'purchase', 'manual', NULL, NULL, NULL, 'Animal Gear Depot', 'Control gear inventory.', 6, '2026-03-02 08:30:00'),
(10, 10, 'stock_in', 10, 0, 10, 'purchase', 'manual', NULL, 'SYR-2026-05', '2028-01-31', 'VetSupply Samar', 'Initial syringe stock.', 3, '2026-03-02 08:40:00'),
(11, 1, 'stock_out', -1, 30, 29, 'dispensed', 'medical_record', 1, 'ARV-2026-01', '2027-02-28', NULL, 'Vaccination for Luna.', 3, '2026-03-05 10:20:00'),
(12, 2, 'stock_out', -1, 20, 19, 'dispensed', 'medical_record', 2, 'DHPP-2026-04', '2027-03-31', NULL, 'DHPP vaccination for Bruno.', 3, '2026-03-06 14:15:00'),
(13, 4, 'stock_out', -10, 120, 110, 'dispensed', 'medical_record', 6, NULL, NULL, NULL, 'Treatment course for Mango.', 3, '2026-03-12 15:00:00'),
(14, 5, 'stock_out', -2, 70, 68, 'dispensed', 'medical_record', 7, 'DEW-2026-03', '2027-01-15', NULL, 'Deworming tablets for Pepper and Milo.', 4, '2026-03-14 09:30:00'),
(15, 8, 'stock_out', -1, 18, 17, 'usage', 'manual', NULL, 'DIS-2026-01', '2027-05-30', NULL, 'Kennel sanitation cycle for quarantine wing.', 6, '2026-03-15 17:00:00'),
(16, 9, 'stock_out', -3, 25, 22, 'usage', 'animal', 10, NULL, NULL, NULL, 'Three leashes issued for field impound response.', 12, '2026-03-18 07:45:00'),
(17, 10, 'stock_out', -1, 10, 9, 'usage', 'medical_record', 3, 'SYR-2026-05', '2028-01-31', NULL, 'Syringe used for feline vaccination.', 4, '2026-03-08 11:10:00');

-- ============================================================================
-- 14. ANIMALS, PHOTOS, AND QR CODES
-- ============================================================================
INSERT INTO `animals`
(`id`, `animal_id`, `name`, `species`, `breed_id`, `breed_other`, `gender`, `age_years`, `age_months`, `color_markings`, `size`, `weight_kg`, `distinguishing_features`, `special_needs_notes`, `intake_type`, `intake_date`, `location_found`, `barangay_of_origin`, `impoundment_order_number`, `authority_name`, `authority_position`, `authority_contact`, `brought_by_name`, `brought_by_contact`, `brought_by_address`, `impounding_officer_name`, `surrender_reason`, `condition_at_intake`, `vaccination_status_at_intake`, `temperament`, `microchip_number`, `spay_neuter_status`, `status`, `status_reason`, `status_changed_at`, `outcome_date`, `created_by`, `updated_by`) VALUES
(1, 'A-2026-0001', 'Luna', 'Dog', 2, NULL, 'Female', 2, 6, 'Black with tan paws', 'Medium', 16.40, 'Small scar on left ear.', NULL, 'Stray', '2026-03-03 08:15:00', 'Public Market loading area', 'Poblacion 1', NULL, 'Catarman Municipal Pound', 'Field Response Team', '09170002001', NULL, NULL, NULL, 'Ivy Lopez', NULL, 'Healthy', 'Unknown', 'Friendly', NULL, 'No', 'Available', 'Completed stray holding period and cleared for adoption.', '2026-03-15 10:00:00', NULL, 5, 5),
(2, 'A-2026-0002', 'Bruno', 'Dog', 5, NULL, 'Male', 4, 0, 'Brown saddle coat', 'Large', 28.10, 'Alert stance and shepherd ears.', NULL, 'Confiscated', '2026-03-04 09:30:00', 'Near diversion road', 'Dalakit', 'IMP-2026-0042', 'Catarman LGU', 'Animal Control Officer', '09170002002', NULL, NULL, NULL, 'Jose Tan', 'Confiscated during leash law enforcement sweep.', 'Healthy', 'Partial', 'Friendly', 'MC-BRUNO-2026', 'Yes', 'Under Medical Care', 'Post-vaccination observation and skin treatment.', '2026-03-12 16:00:00', NULL, 5, 5),
(3, 'A-2026-0003', 'Misty', 'Cat', 20, NULL, 'Female', 1, 8, 'Cream and gray points', 'Small', 3.80, 'Blue eyes and clipped right ear tip.', NULL, 'Stray', '2026-03-05 11:20:00', 'Barangay hall backyard', 'Molave', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Sick', 'Unknown', 'Shy', NULL, 'No', 'Available', 'Recovered from upper respiratory infection.', '2026-03-18 09:00:00', NULL, 6, 6),
(4, 'A-2026-0004', 'Pepper', 'Cat', 18, NULL, 'Male', 3, 0, 'Black with white chest patch', 'Small', 4.20, 'Slight limp on rear leg.', 'Requires calmer handling during medication.', 'Owner Surrender', '2026-03-06 13:45:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Rica Velasco', '09181231114', 'Barangay Jose Abad Santos, Catarman', NULL, 'Owner relocating and unable to keep pet.', 'Injured', 'None', 'Shy', NULL, 'Unknown', 'Under Medical Care', 'Recovering from soft tissue injury and deworming.', '2026-03-14 09:15:00', NULL, 5, 5),
(5, 'A-2026-0005', 'Mango', 'Dog', 1, NULL, 'Male', 1, 2, 'Tan short coat', 'Medium', 12.70, 'Docked-looking tail from old injury.', NULL, 'Stray', '2026-03-07 07:50:00', 'Barangay Macagtas tricycle terminal', 'Macagtas', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Malnourished', 'Unknown', 'Friendly', NULL, 'No', 'Under Medical Care', 'Currently on antibiotic treatment for wound infection.', '2026-03-12 15:00:00', NULL, 6, 6),
(6, 'A-2026-0006', 'Coco', 'Dog', 6, NULL, 'Female', 5, 0, 'White and brown long coat', 'Small', 6.20, 'Long lashes and overbite.', NULL, 'Owner Surrender', '2026-03-08 10:10:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Nina Ramos', '09181235555', 'Barangay Yakal, Catarman', NULL, 'Medical costs became too high for previous owner.', 'Healthy', 'Up to date', 'Friendly', 'MC-COCO-2021', 'Yes', 'Adopted', 'Completed adoption with post-care packet released.', '2026-03-26 14:30:00', '2026-03-26 14:30:00', 5, 7),
(7, 'A-2026-0007', 'Shadow', 'Dog', 13, NULL, 'Male', 3, 6, 'Dark brindle coat', 'Large', 24.90, 'Distinct white streak on muzzle.', NULL, 'Transfer', '2026-03-09 15:00:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Samar Rescue Network', '09181239991', 'Allen, Northern Samar', NULL, NULL, 'Aggressive', 'Unknown', 'Aggressive', NULL, 'Unknown', 'Quarantine', 'Behavioral assessment and quarantine intake.', '2026-03-09 15:00:00', NULL, 2, 2),
(8, 'A-2026-0008', 'Bella', 'Dog', 3, NULL, 'Female', 6, 0, 'Golden coat', 'Large', 27.30, 'Small benign lump removed near flank.', NULL, 'Owner Surrender', '2026-03-10 12:40:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Edwin Cruz', '09181236666', 'Barangay Acacia, Catarman', NULL, 'Family emigrating overseas.', 'Healthy', 'Partial', 'Friendly', 'MC-BELLA-2019', 'Yes', 'Available', 'Cleared post-surgery and ready for matching.', '2026-03-21 16:30:00', NULL, 5, 5),
(9, 'A-2026-0009', 'Tala', 'Cat', 19, NULL, 'Female', 2, 4, 'White long coat', 'Small', 3.60, 'Amber eyes and fluffy tail.', NULL, 'Born in Shelter', '2026-03-11 09:00:00', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Healthy', 'Up to date', 'Friendly', NULL, 'Yes', 'Available', 'Ready for indoor-home adoption.', '2026-03-20 08:00:00', NULL, 6, 6),
(10, 'A-2026-0010', 'Rex', 'Dog', 14, NULL, 'Male', 7, 0, 'Black and mahogany coat', 'Large', 31.40, 'Healed chest scar from previous tethering injury.', NULL, 'Confiscated', '2026-03-12 06:30:00', 'Barangay Narra roadside', 'Narra', 'IMP-2026-0048', 'Catarman LGU', 'Animal Control Officer', '09170002002', NULL, NULL, NULL, 'Jose Tan', NULL, 'Aggressive', 'Unknown', 'Aggressive', NULL, 'No', 'Transferred', 'Transferred to provincial partner facility for long-term behavior rehab.', '2026-03-22 09:00:00', '2026-03-22 09:00:00', 12, 12),
(11, 'A-2026-0011', 'Milo', 'Cat', 18, NULL, 'Male', 0, 10, 'Orange tabby', 'Small', 2.90, 'Crinkled left ear from healed hematoma.', NULL, 'Stray', '2026-03-13 14:00:00', 'School canteen area', 'Jose Abad Santos', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'Healthy', 'Unknown', 'Friendly', NULL, 'No', 'Available', 'Completed intake and deworming.', '2026-03-19 10:45:00', NULL, 6, 6),
(12, 'A-2026-0012', 'Harley', 'Dog', 16, 'Mixed shepherd-husky', 'Male', 5, 0, 'Gray and white thick coat', 'Large', 29.80, 'Cloudy left eye and chronic hind-leg weakness.', 'Requires pain management and assisted mobility on long walks.', 'Owner Surrender', '2026-03-14 16:20:00', NULL, NULL, NULL, NULL, NULL, NULL, 'Mylene Ortiz', '09181237777', 'Barangay Sampaguita, Catarman', NULL, 'End-of-life surrender for advanced chronic condition.', 'Sick', 'Partial', 'Shy', 'MC-HARLEY-2018', 'Yes', 'Deceased', 'Humanely euthanized after non-responsive palliative decline.', '2026-03-24 11:45:00', '2026-03-24 11:45:00', 2, 2);

INSERT INTO `animal_photos`
(`id`, `animal_id`, `file_path`, `file_name`, `file_size_bytes`, `mime_type`, `is_primary`, `sort_order`, `uploaded_by`) VALUES
(1, 1, 'favicon.png', 'luna-profile.png', 4096, 'image/png', 1, 0, 5),
(2, 2, 'favicon.png', 'bruno-profile.png', 4096, 'image/png', 1, 0, 5),
(3, 3, 'favicon.png', 'misty-profile.png', 4096, 'image/png', 1, 0, 6),
(4, 4, 'favicon.png', 'pepper-profile.png', 4096, 'image/png', 1, 0, 5),
(5, 5, 'favicon.png', 'mango-profile.png', 4096, 'image/png', 1, 0, 6),
(6, 6, 'favicon.png', 'coco-profile.png', 4096, 'image/png', 1, 0, 5),
(7, 8, 'favicon.png', 'bella-profile.png', 4096, 'image/png', 1, 0, 5),
(8, 9, 'favicon.png', 'tala-profile.png', 4096, 'image/png', 1, 0, 6),
(9, 11, 'favicon.png', 'milo-profile.png', 4096, 'image/png', 1, 0, 6);

INSERT INTO `animal_qr_codes`
(`id`, `animal_id`, `qr_data`, `file_path`, `generated_by`) VALUES
(1, 1, 'https://catarman-shelter.local/animals/1', 'icon-192.png', 5),
(2, 2, 'https://catarman-shelter.local/animals/2', 'icon-192.png', 5),
(3, 6, 'https://catarman-shelter.local/animals/6', 'icon-192.png', 5),
(4, 9, 'https://catarman-shelter.local/animals/9', 'icon-192.png', 6);

-- ============================================================================
-- 15. KENNEL ASSIGNMENTS AND MAINTENANCE
-- ============================================================================
INSERT INTO `kennel_assignments`
(`id`, `kennel_id`, `animal_id`, `assigned_at`, `released_at`, `transfer_reason`, `assigned_by`, `released_by`) VALUES
(1, 3, 1, '2026-03-03 09:00:00', NULL, NULL, 5, NULL),
(2, 7, 2, '2026-03-04 10:00:00', NULL, NULL, 5, NULL),
(3, 12, 3, '2026-03-05 12:00:00', NULL, NULL, 6, NULL),
(4, 13, 4, '2026-03-06 14:15:00', NULL, NULL, 5, NULL),
(5, 4, 5, '2026-03-07 08:30:00', NULL, NULL, 6, NULL),
(6, 1, 6, '2026-03-08 11:00:00', '2026-03-26 14:45:00', 'Released after completed adoption.', 5, 5),
(7, 18, 7, '2026-03-09 15:20:00', NULL, NULL, 12, NULL),
(8, 8, 8, '2026-03-10 13:30:00', NULL, NULL, 5, NULL),
(9, 14, 9, '2026-03-11 09:30:00', NULL, NULL, 6, NULL),
(10, 17, 10, '2026-03-12 07:15:00', '2026-03-22 08:50:00', 'Transferred to partner rehabilitation facility.', 12, 12),
(11, 11, 11, '2026-03-13 14:30:00', NULL, NULL, 6, NULL),
(12, 19, 12, '2026-03-14 17:00:00', '2026-03-24 12:10:00', 'Released after euthanasia procedure.', 2, 2);

INSERT INTO `kennel_maintenance_logs`
(`id`, `kennel_id`, `maintenance_type`, `description`, `scheduled_date`, `completed_at`, `performed_by`) VALUES
(1, 18, 'Sanitization', 'Quarantine kennel deep sanitation after intake of Shadow.', '2026-03-10', '2026-03-10 17:20:00', 6),
(2, 19, 'Inspection', 'Isolation kennel ventilation and drainage inspection.', '2026-03-14', '2026-03-14 16:45:00', 12),
(3, 7, 'Cleaning', 'Routine high-pressure wash and feed bowl replacement.', '2026-03-16', '2026-03-16 18:00:00', 5),
(4, 14, 'Repair', 'Latch replacement and scratch guard reinforcement.', '2026-03-18', '2026-03-18 15:30:00', 12);

-- ============================================================================
-- 16. MEDICAL RECORDS AND SHARED MEDICAL SECTIONS
-- ============================================================================
INSERT INTO `medical_records`
(`id`, `animal_id`, `procedure_type`, `record_date`, `general_notes`, `veterinarian_id`, `created_by`, `updated_by`) VALUES
(1, 1, 'vaccination', '2026-03-05 10:00:00', 'Initial intake vaccination after observation clearance.', 3, 3, 3),
(2, 2, 'vaccination', '2026-03-06 14:00:00', 'DHPP update and skin check before kennel integration.', 3, 3, 3),
(3, 3, 'vaccination', '2026-03-08 11:00:00', 'Feline core vaccine administered after URI recovery.', 4, 4, 4),
(4, 8, 'surgery', '2026-03-11 08:30:00', 'Benign flank mass excision completed successfully.', 3, 3, 3),
(5, 4, 'examination', '2026-03-12 09:00:00', 'Mobility review and pain assessment after surrender intake.', 4, 4, 4),
(6, 5, 'treatment', '2026-03-12 14:30:00', 'Bite wound infection treatment and 5-day oral antibiotic course.', 3, 3, 3),
(7, 11, 'deworming', '2026-03-14 09:00:00', 'Routine intake deworming for stray kitten.', 4, 4, 4),
(8, 12, 'euthanasia', '2026-03-24 11:00:00', 'End-of-life humane euthanasia after palliative case review.', 3, 2, 2);

INSERT INTO `vaccination_records`
(`medical_record_id`, `vaccine_name`, `vaccine_brand`, `batch_lot_number`, `dosage_ml`, `route`, `injection_site`, `dose_number`, `next_due_date`, `adverse_reactions`) VALUES
(1, 'Anti-rabies', 'Defensor 3', 'ARV-2026-01', 1.00, 'Subcutaneous', 'Left shoulder', 1, '2027-03-05', NULL),
(2, 'DHPP 5-in-1', 'CaniShield', 'DHPP-2026-04', 1.00, 'Subcutaneous', 'Right shoulder', 2, '2027-03-06', 'Observed mild injection-site soreness for 24 hours.'),
(3, 'FVRCP 4-in-1', 'Felocell', 'FVRCP-2026-02', 1.00, 'Subcutaneous', 'Left flank', 1, '2027-03-08', NULL);

INSERT INTO `surgery_records`
(`medical_record_id`, `surgery_type`, `pre_op_weight_kg`, `anesthesia_type`, `anesthesia_drug`, `anesthesia_dosage`, `duration_minutes`, `surgical_notes`, `complications`, `post_op_instructions`, `follow_up_date`) VALUES
(4, 'Tumor Removal', 27.30, 'General', 'Ketamine + Diazepam', '5 ml induction', 75, 'Localized mass removed from left flank and sent for pathology review.', NULL, 'Keep incision dry, restrict running, return for suture check.', '2026-03-18');

INSERT INTO `examination_records`
(`medical_record_id`, `weight_kg`, `temperature_celsius`, `heart_rate_bpm`, `respiratory_rate`, `body_condition_score`, `eyes_status`, `eyes_notes`, `ears_status`, `ears_notes`, `teeth_gums_status`, `teeth_gums_notes`, `skin_coat_status`, `skin_coat_notes`, `musculoskeletal_status`, `musculoskeletal_notes`, `overall_assessment`, `recommendations`) VALUES
(5, 4.20, 38.4, 156, 32, 4, 'Normal', NULL, 'Normal', NULL, 'Normal', 'Mild tartar only.', 'Normal', NULL, 'Abnormal', 'Rear leg strain with tenderness on extension.', 'Stable soft tissue injury with improving gait.', 'Continue cage rest, monitor pain response, schedule recheck in one week.');

INSERT INTO `treatment_records`
(`medical_record_id`, `diagnosis`, `medication_name`, `dosage`, `route`, `frequency`, `duration_days`, `start_date`, `end_date`, `quantity_dispensed`, `inventory_item_id`, `special_instructions`) VALUES
(6, 'Localized bite wound infection', 'Amoxicillin 250mg', '1 capsule', 'Oral', 'Twice daily', 5, '2026-03-12', '2026-03-17', 10, 4, 'Administer with food and clean wound site before evening dose.');

INSERT INTO `deworming_records`
(`medical_record_id`, `dewormer_name`, `brand`, `dosage`, `weight_at_treatment_kg`, `next_due_date`) VALUES
(7, 'Pyrantel Pamoate', 'Pyrantel Dewormer', '1 tablet', 2.90, '2026-06-14');

INSERT INTO `euthanasia_records`
(`medical_record_id`, `reason_category`, `reason_details`, `authorized_by`, `method`, `drug_used`, `drug_dosage`, `time_of_death`, `death_confirmed`, `disposal_method`) VALUES
(8, 'Medical', 'Advanced chronic pain and declining quality of life with non-responsive supportive management.', 2, 'IV Injection (Pentobarbital)', 'Pentobarbital sodium', '12 ml', '2026-03-24 11:45:00', 1, 'Cremation');

INSERT INTO `medical_vital_signs`
(`medical_record_id`, `weight_kg`, `temperature_celsius`, `heart_rate_bpm`, `respiratory_rate`, `body_condition_score`) VALUES
(1, 16.40, 38.6, 102, 24, 5),
(2, 28.10, 38.5, 96, 22, 5),
(3, 3.80, 38.2, 154, 28, 4),
(4, 27.30, 38.7, 94, 20, 5),
(5, 4.20, 38.4, 156, 32, 4),
(6, 12.70, 39.1, 118, 28, 3),
(7, 2.90, 38.3, 162, 30, 4),
(8, 29.80, 39.0, 112, 26, 2);

INSERT INTO `medical_prescriptions`
(`medical_record_id`, `medicine_name`, `dosage`, `frequency`, `duration`, `quantity`, `instructions`, `sort_order`) VALUES
(5, 'Meloxicam', '0.5 ml', 'Once daily', '5 days', 1, 'Give after feeding and monitor mobility each morning.', 0),
(6, 'Amoxicillin 250mg', '1 capsule', 'Twice daily', '5 days', 10, 'Complete full course even if the wound looks dry sooner.', 0),
(6, 'Chlorhexidine solution', 'Apply thin layer', 'Twice daily', '5 days', 1, 'Clean wound before each application.', 1);

INSERT INTO `medical_lab_results`
(`medical_record_id`, `test_name`, `result_value`, `normal_range`, `status`, `date_conducted`, `remarks`, `attachment_path`, `sort_order`) VALUES
(4, 'Mass Cytology', 'Benign soft tissue mass', 'No malignant cells observed', 'Normal', '2026-03-13', 'Cytology report consistent with benign lesion.', 'favicon.png', 0),
(5, 'Lameness Assessment', 'Grade 2 soft tissue strain', 'Grade 0 expected', 'Abnormal', '2026-03-12', 'No fracture suspected; conservative management advised.', 'favicon.png', 0),
(8, 'CBC Review', 'Inflammatory markers elevated', 'WBC within normal range', 'Abnormal', '2026-03-23', 'Supported palliative decline discussion with surrendering owner.', 'favicon.png', 0);

-- ============================================================================
-- 17. ADOPTION PIPELINE
-- ============================================================================
INSERT INTO `adoption_applications`
(`id`, `application_number`, `adopter_id`, `animal_id`, `status`, `preferred_species`, `preferred_breed`, `preferred_age_min`, `preferred_age_max`, `preferred_size`, `preferred_gender`, `housing_type`, `housing_ownership`, `has_yard`, `yard_size`, `num_adults`, `num_children`, `children_ages`, `existing_pets_description`, `previous_pet_experience`, `vet_reference_name`, `vet_reference_clinic`, `vet_reference_contact`, `valid_id_path`, `digital_signature_path`, `agrees_to_policies`, `agrees_to_home_visit`, `agrees_to_return_policy`, `rejection_reason`, `withdrawn_reason`, `created_by`, `updated_by`) VALUES
(1, 'APP-2026-0001', 8, 6, 'completed', 'Dog', 'Shih Tzu', 1, 8, 'Small', 'Female', 'House', 'Owned', 1, 'Medium', 2, 1, '7', 'One senior aspin already vaccinated and indoor-kept.', 'Adopted two rescue cats during college.', 'Dr. Elena Cruz', 'Catarman Vet Clinic', '09181234444', 'favicon.png', NULL, 1, 1, 1, NULL, NULL, 8, 8),
(2, 'APP-2026-0002', 9, 1, 'seminar_completed', 'Dog', 'Labrador Retriever', 1, 5, 'Medium', 'Female', 'House', 'Owned', 1, 'Large', 3, 0, NULL, 'No current pets.', 'Raised aspins in family home for 10 years.', 'Dr. Elena Cruz', 'Catarman Vet Clinic', '09181234444', 'favicon.png', NULL, 1, 1, 1, NULL, NULL, 9, 9),
(3, 'APP-2026-0003', 10, 9, 'interview_scheduled', 'Cat', 'Mixed Breed', 0, 5, 'Small', 'Female', 'Apartment', 'Rented', 0, NULL, 1, 0, NULL, 'No current pets.', 'Fostered kittens for local rescuers.', 'Dr. Marco Salazar', 'North Samar Animal Clinic', '09181232222', 'favicon.png', NULL, 1, 1, 1, NULL, NULL, 10, 10),
(4, 'APP-2026-0004', 11, 8, 'pending_review', 'Dog', 'Golden Retriever', 2, 8, 'Large', 'Female', 'House', 'Owned', 1, 'Large', 4, 2, '9,12', 'Has one neutered male aspin.', 'Long-time dog owner with obedience class experience.', 'Dr. Elena Cruz', 'Catarman Vet Clinic', '09181234444', 'favicon.png', NULL, 1, 1, 1, NULL, NULL, 11, 11),
(5, 'APP-2026-0005', 9, 11, 'rejected', 'Cat', 'Puspin (Pusang Pinoy)', 0, 3, 'Small', 'Male', 'Apartment', 'Rented', 0, NULL, 2, 3, '3,5,8', 'Keeps ornamental birds indoors.', 'Limited cat ownership experience.', 'Dr. Marco Salazar', 'North Samar Animal Clinic', '09181232222', 'favicon.png', NULL, 1, 1, 1, 'Household setup is currently incompatible with the selected cat and home visit findings.', NULL, 9, 9),
(6, 'APP-2026-0006', 10, NULL, 'withdrawn', 'Dog', 'Aspin (Asong Pinoy)', 1, 6, 'Medium', 'Male', 'Condo', 'Rented', 0, NULL, 1, 0, NULL, 'No pets.', 'First-time adopter; withdrew due to relocation.', NULL, NULL, NULL, 'favicon.png', NULL, 1, 1, 1, NULL, 'Adopter moved to a no-pets unit before interview.', 10, 10);

INSERT INTO `adoption_interviews`
(`id`, `application_id`, `scheduled_date`, `interview_type`, `video_call_link`, `location`, `status`, `screening_checklist`, `home_assessment_notes`, `pet_care_knowledge_score`, `overall_recommendation`, `interviewer_notes`, `conducted_by`, `completed_at`) VALUES
(1, 1, '2026-03-20 10:00:00', 'in_person', NULL, 'Catarman Shelter Interview Room', 'completed', '[{"question":"Stable housing","answer":"yes","passed":true},{"question":"Budget for care","answer":"yes","passed":true}]', 'Home visit showed fenced yard and shaded rest area.', 9, 'Approve', 'Adopter is well-prepared and responsive.', 5, '2026-03-20 10:45:00'),
(2, 2, '2026-03-25 14:00:00', 'video_call', 'https://meet.example.com/app-2026-0002', NULL, 'completed', '[{"question":"Household readiness","answer":"yes","passed":true},{"question":"Vet support","answer":"yes","passed":true}]', 'Adopter has completed orientation and provided updated photos of home gate.', 8, 'Conditional', 'Proceed to payment after final kennel visit.', 6, '2026-03-25 14:35:00'),
(3, 3, '2026-03-31 09:30:00', 'in_person', NULL, 'Catarman Shelter Interview Room', 'scheduled', NULL, NULL, NULL, NULL, 'Initial interview schedule confirmed by applicant.', 5, NULL),
(4, 5, '2026-03-21 13:00:00', 'in_person', NULL, 'Catarman Shelter Interview Room', 'completed', '[{"question":"Safe separation from prey animals","answer":"no","passed":false}]', 'Observed significant mismatch between home environment and cat safety needs.', 4, 'Reject', 'Recommend applicant reconsider after environment changes.', 6, '2026-03-21 13:40:00');

INSERT INTO `adoption_seminars`
(`id`, `title`, `scheduled_date`, `end_time`, `location`, `capacity`, `facilitator_id`, `description`, `status`, `created_by`) VALUES
(1, 'Responsible Pet Ownership Orientation', '2026-03-22 09:00:00', '2026-03-22 12:00:00', 'Catarman Shelter Training Hall', 25, 5, 'Required orientation covering adopter duties, transition planning, and post-adoption care.', 'completed', 5),
(2, 'April Adopter Onboarding Session', '2026-04-02 09:00:00', '2026-04-02 12:00:00', 'Catarman Shelter Training Hall', 30, 6, 'Monthly seminar for applicants moving into interview and release stages.', 'scheduled', 5);

INSERT INTO `seminar_attendees`
(`id`, `seminar_id`, `application_id`, `attendance_status`, `marked_by`, `marked_at`) VALUES
(1, 1, 1, 'attended', 5, '2026-03-22 11:45:00'),
(2, 1, 2, 'attended', 6, '2026-03-22 11:45:00'),
(3, 2, 3, 'registered', NULL, NULL);

INSERT INTO `adoption_completions`
(`id`, `application_id`, `animal_id`, `adopter_id`, `completion_date`, `payment_confirmed`, `contract_signed`, `contract_signature_path`, `medical_records_provided`, `spay_neuter_agreement`, `certificate_path`, `notes`, `processed_by`) VALUES
(1, 1, 6, 8, '2026-03-26 14:30:00', 1, 1, 'favicon.png', 1, 1, 'favicon.png', 'Adopter received vaccination card, feeding instructions, and follow-up schedule.', 7);

-- ============================================================================
-- 18. BILLING AND PAYMENTS
-- ============================================================================
INSERT INTO `invoices`
(`id`, `invoice_number`, `payor_type`, `payor_user_id`, `payor_name`, `payor_contact`, `payor_address`, `animal_id`, `application_id`, `subtotal`, `tax_amount`, `total_amount`, `amount_paid`, `payment_status`, `issue_date`, `due_date`, `notes`, `terms`, `pdf_path`, `created_by`, `updated_by`) VALUES
(1, 'INV-2026-0001', 'adopter', 8, 'Sophia Mercado', '09181230008', 'Purok 2, Barangay Macagtas, Catarman, Northern Samar', 6, 1, 800.00, 0.00, 800.00, 800.00, 'paid', '2026-03-24', '2026-03-26', 'Completed adoption processing and release fees for Coco.', 'Payment due before animal release.', 'favicon.png', 7, 7),
(2, 'INV-2026-0002', 'adopter', 9, 'Daniel Fernandez', '09181230009', 'Zone 4, Barangay Narra, Catarman, Northern Samar', 1, 2, 800.00, 0.00, 800.00, 300.00, 'partial', '2026-03-25', '2026-03-30', 'Reservation and initial processing payment for Luna adoption.', 'Remaining balance due before turnover.', 'favicon.png', 7, 7),
(3, 'INV-2026-0003', 'adopter', 9, 'Daniel Fernandez', '09181230009', 'Zone 4, Barangay Narra, Catarman, Northern Samar', 2, NULL, 450.00, 0.00, 450.00, 0.00, 'unpaid', '2026-03-12', '2026-03-19', 'Medical cost recovery for confiscated dog under claimant review.', 'Pay within seven days of notice.', NULL, 7, 7),
(4, 'INV-2026-0004', 'adopter', 9, 'Daniel Fernandez', '09181230009', 'Zone 4, Barangay Narra, Catarman, Northern Samar', 11, 5, 150.00, 0.00, 150.00, 150.00, 'paid', '2026-03-21', '2026-03-28', 'Application fee collected before rejection closure.', 'Non-refundable processing fee.', 'favicon.png', 7, 7),
(5, 'INV-2026-0005', 'adopter', 11, 'Mark Salvador', '09181230011', 'Rizal Avenue, Barangay Jose Abad Santos, Catarman, Northern Samar', 12, NULL, 1000.00, 0.00, 1000.00, 0.00, 'unpaid', '2026-03-24', '2026-03-31', 'Euthanasia and cremation service billing for owner surrender case.', 'Compassionate care discount already applied.', NULL, 7, 7);

INSERT INTO `invoice_line_items`
(`id`, `invoice_id`, `fee_schedule_id`, `description`, `quantity`, `unit_price`, `sort_order`) VALUES
(1, 1, 1, 'Adoption processing fee', 1, 500.00, 0),
(2, 1, NULL, 'Microchip registration transfer packet', 1, 300.00, 1),
(3, 2, 1, 'Adoption processing fee', 1, 500.00, 0),
(4, 2, NULL, 'Reservation and seminar fee', 1, 300.00, 1),
(5, 3, NULL, 'Medical observation and skin treatment package', 1, 450.00, 0),
(6, 4, NULL, 'Application processing fee', 1, 150.00, 0),
(7, 5, NULL, 'Euthanasia procedure', 1, 700.00, 0),
(8, 5, NULL, 'Cremation and handling fee', 1, 300.00, 1);

INSERT INTO `payments`
(`id`, `invoice_id`, `payment_number`, `amount`, `payment_method`, `reference_number`, `payment_date`, `receipt_number`, `receipt_path`, `notes`, `received_by`) VALUES
(1, 1, 'PAY-2026-0001', 800.00, 'Cash', 'OR-2026-1041', '2026-03-26 14:10:00', 'OR-2026-1041', 'favicon.png', 'Full payment before release of Coco.', 7),
(2, 2, 'PAY-2026-0002', 300.00, 'GCash', 'GCASH-250326-LUNA', '2026-03-25 16:00:00', 'OR-2026-1042', 'favicon.png', 'Initial payment pending final kennel visit.', 7),
(3, 4, 'PAY-2026-0003', 150.00, 'Cash', 'OR-2026-1038', '2026-03-21 14:05:00', 'OR-2026-1038', 'favicon.png', 'Application fee payment captured on same day.', 7);

-- ============================================================================
-- 19. NOTIFICATIONS, BACKUPS, AND AUDIT TRAIL
-- ============================================================================
INSERT INTO `notifications`
(`id`, `user_id`, `type`, `title`, `message`, `link`, `is_read`, `read_at`, `created_at`) VALUES
(1, 5, 'adoption_application', 'Interview Completed', 'Application APP-2026-0001 interview was completed and is ready for final billing review.', '/adoption/view?id=1', 1, '2026-03-20 11:00:00', '2026-03-20 10:50:00'),
(2, 7, 'system', 'New Partial Payment', 'Invoice INV-2026-0002 received a partial payment of PHP 300.00.', '/billing/invoices/2', 0, NULL, '2026-03-25 16:05:00'),
(3, 3, 'medical_followup', 'Lab Result Added', 'Bella has a new cytology lab result attached to surgery record #4.', '/medical/records/4', 0, NULL, '2026-03-13 10:30:00'),
(4, 12, 'system', 'Transfer Completed', 'Rex was transferred to the provincial rehabilitation partner facility.', '/animals/view?id=10', 1, '2026-03-22 09:10:00', '2026-03-22 09:05:00'),
(5, 2, 'system', 'Backup Completed', 'Nightly system backup for 2026-03-27 finished successfully.', '/settings/backups', 0, NULL, '2026-03-27 23:30:00');

INSERT INTO `system_backups`
(`id`, `backup_type`, `file_path`, `file_size_bytes`, `checksum_sha256`, `status`, `tables_included`, `error_message`, `started_at`, `completed_at`, `restored_at`, `created_by`, `restored_by`) VALUES
(1, 'full', 'storage/backups/catarman-demo-2026-03-27.sql.gz', 5242880, '5f3c8af4d946b7e7e6ef6ff89f6b7d2f50e8b4df7f3d18ca8f0a6a9e1db3ce71', 'completed', '["users","animals","medical_records","adoption_applications","invoices","payments","inventory_items"]', NULL, '2026-03-27 23:00:00', '2026-03-27 23:28:00', NULL, 1, NULL);

INSERT INTO `audit_logs`
(`id`, `user_id`, `action`, `module`, `record_table`, `record_id`, `old_values`, `new_values`, `ip_address`, `user_agent`, `request_id`, `created_at`) VALUES
(1, 5, 'create', 'animals', 'animals', 1, NULL, '{"animal_id":"A-2026-0001","name":"Luna","status":"Available"}', '192.168.1.10', 'Seeder Demo Agent/1.0', '9af3c330-3f12-4d46-a6a5-4cf628a70101', '2026-03-03 08:20:00'),
(2, 3, 'create', 'medical', 'medical_records', 1, NULL, '{"animal_id":1,"procedure_type":"vaccination","record_date":"2026-03-05 10:00:00"}', '192.168.1.11', 'Seeder Demo Agent/1.0', '9af3c330-3f12-4d46-a6a5-4cf628a70102', '2026-03-05 10:05:00'),
(3, 5, 'update', 'adoptions', 'adoption_applications', 1, '{"status":"seminar_completed"}', '{"status":"completed"}', '192.168.1.12', 'Seeder Demo Agent/1.0', '9af3c330-3f12-4d46-a6a5-4cf628a70103', '2026-03-26 14:35:00'),
(4, 7, 'create', 'billing', 'payments', 1, NULL, '{"invoice_id":1,"amount":800,"payment_method":"Cash"}', '192.168.1.13', 'Seeder Demo Agent/1.0', '9af3c330-3f12-4d46-a6a5-4cf628a70104', '2026-03-26 14:11:00'),
(5, 1, 'create', 'settings', 'system_backups', 1, NULL, '{"file_path":"storage/backups/catarman-demo-2026-03-27.sql.gz","status":"completed"}', '127.0.0.1', 'Seeder Demo Agent/1.0', '9af3c330-3f12-4d46-a6a5-4cf628a70105', '2026-03-27 23:29:00');

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SEEDER SUMMARY
-- ============================================================================
-- Roles:               6 (super_admin, shelter_head, vet, staff, clerk, adopter)
-- Permissions:         34 (across 9 modules)
-- Role-Permissions:    Mapped for all 5 staff roles
-- Users:               12 (staff, clerks, veterinarians, and adopters)
-- Breeds:              27 (17 dogs + 10 cats, Philippine-relevant)
-- Inventory Categories: 5
-- Inventory Items:     10 with 17 stock transactions
-- Fee Schedule:        15 items (adoption, surrender, impound, medical, etc.)
-- Kennels:             20 (across 4 zones + quarantine)
-- Animals:             12 with photos, QR codes, and kennel assignments
-- Medical Records:     8 across vaccination, surgery, exam, treatment, deworming, and euthanasia
-- Adoption Records:    6 applications, 4 interviews, 2 seminars, 1 completion
-- Billing Records:     5 invoices, 8 line items, 3 payments
-- Notifications:       5 with 1 completed backup and 5 audit trail entries
-- System Settings:     9 operational defaults
-- ID Sequences:        4 initialized for 2026 seeded records
-- Report Templates:    6 built-in
-- ============================================================================
