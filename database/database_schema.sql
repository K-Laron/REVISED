-- ============================================================================
-- Catarman Dog Pound & Animal Shelter Management System
-- Production Database Schema (Atomic Design)
-- MySQL 8.0+
-- ============================================================================
-- Naming Conventions:
--   Tables:      snake_case, plural (e.g., animals, medical_records)
--   Columns:     snake_case (e.g., created_at, animal_id)
--   PKs:         id (BIGINT UNSIGNED AUTO_INCREMENT)
--   FKs:         {referenced_table_singular}_id (e.g., user_id, animal_id)
--   Indexes:     idx_{table}_{column(s)}
--   Uniques:     uq_{table}_{column(s)}
--   FKs:         fk_{table}_{referenced_table}
--   Timestamps:  created_at, updated_at, deleted_at (soft deletes)
--   Audit cols:  created_by, updated_by (FK to users.id)
-- ============================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO';

-- ============================================================================
-- 1. AUTHENTICATION & USER MANAGEMENT
-- ============================================================================

CREATE TABLE IF NOT EXISTS `roles` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL COMMENT 'e.g., super_admin, shelter_head, veterinarian, shelter_staff, billing_clerk, adopter',
  `display_name` VARCHAR(100) NOT NULL,
  `description` TEXT NULL,
  `is_system` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = cannot be deleted',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'e.g., animals.create, animals.read, medical.create',
  `display_name` VARCHAR(150) NOT NULL,
  `module` VARCHAR(50) NOT NULL COMMENT 'e.g., animals, medical, adoptions, billing, inventory, users, reports, settings',
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_permissions_name` (`name`),
  KEY `idx_permissions_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `permission_id` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_role_permissions` (`role_id`, `permission_id`),
  CONSTRAINT `fk_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role_id` BIGINT UNSIGNED NOT NULL,
  `username` VARCHAR(100) NULL,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'bcrypt hash',
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `middle_name` VARCHAR(100) NULL,
  `phone` VARCHAR(20) NULL,
  `address_line1` VARCHAR(255) NULL,
  `address_line2` VARCHAR(255) NULL,
  `city` VARCHAR(100) NULL,
  `province` VARCHAR(100) NULL,
  `zip_code` VARCHAR(10) NULL,
  `avatar_path` VARCHAR(500) NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `email_verified_at` DATETIME NULL,
  `force_password_change` TINYINT(1) NOT NULL DEFAULT 0,
  `failed_login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
  `locked_until` DATETIME NULL,
  `last_login_at` DATETIME NULL,
  `last_login_ip` VARCHAR(45) NULL,
  `deleted_at` DATETIME NULL,
  `deleted_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `idx_users_role` (`role_id`),
  KEY `idx_users_is_deleted` (`is_deleted`),
  KEY `idx_users_is_active` (`is_active`),
  KEY `idx_users_name` (`last_name`, `first_name`),
  CONSTRAINT `fk_users_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `session_token_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash of session token',
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(500) NULL,
  `expires_at` DATETIME NOT NULL,
  `last_activity_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sessions_token` (`session_token_hash`),
  KEY `idx_sessions_user` (`user_id`),
  KEY `idx_sessions_expires` (`expires_at`),
  CONSTRAINT `fk_sessions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL COMMENT 'SHA-256 hash, never store plaintext',
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reset_token` (`token_hash`),
  KEY `idx_reset_user` (`user_id`),
  KEY `idx_reset_expires` (`expires_at`),
  CONSTRAINT `fk_reset_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 2. ANIMAL MANAGEMENT
-- ============================================================================

CREATE TABLE IF NOT EXISTS `breeds` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `species` VARCHAR(20) NOT NULL COMMENT 'Dog, Cat, Other',
  `name` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_breeds_species_name` (`species`, `name`),
  KEY `idx_breeds_species` (`species`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` VARCHAR(20) NOT NULL COMMENT 'Formatted: A-YYYY-NNNN',
  `name` VARCHAR(100) NULL,
  `species` VARCHAR(20) NOT NULL COMMENT 'Dog, Cat, Other',
  `breed_id` BIGINT UNSIGNED NULL,
  `breed_other` VARCHAR(100) NULL COMMENT 'If breed is Unknown/Mixed/Other',
  `gender` VARCHAR(10) NOT NULL COMMENT 'Male, Female',
  `age_years` TINYINT UNSIGNED NULL,
  `age_months` TINYINT UNSIGNED NULL,
  `color_markings` VARCHAR(255) NULL,
  `size` VARCHAR(20) NOT NULL COMMENT 'Small, Medium, Large, Extra Large',
  `weight_kg` DECIMAL(5,2) NULL,
  `distinguishing_features` TEXT NULL,
  `intake_type` VARCHAR(30) NOT NULL COMMENT 'Stray, Owner Surrender, Confiscated, Transfer, Born in Shelter',
  `intake_date` DATETIME NOT NULL,
  `location_found` VARCHAR(500) NULL COMMENT 'For strays',
  `brought_by_name` VARCHAR(200) NULL,
  `brought_by_contact` VARCHAR(20) NULL,
  `brought_by_address` VARCHAR(500) NULL,
  `surrender_reason` TEXT NULL,
  `condition_at_intake` VARCHAR(30) NOT NULL COMMENT 'Healthy, Injured, Sick, Malnourished, Aggressive',
  `temperament` VARCHAR(20) NOT NULL DEFAULT 'Unknown' COMMENT 'Friendly, Shy, Aggressive, Unknown',
  `status` VARCHAR(30) NOT NULL DEFAULT 'Available' COMMENT 'Available, Under Medical Care, In Adoption Process, Adopted, Deceased, Transferred, Quarantine',
  `status_reason` TEXT NULL,
  `status_changed_at` DATETIME NULL,
  `outcome_date` DATETIME NULL COMMENT 'Date of adoption, transfer, death, etc.',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL,
  `deleted_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_animals_animal_id` (`animal_id`),
  KEY `idx_animals_species` (`species`),
  KEY `idx_animals_status` (`status`),
  KEY `idx_animals_intake_date` (`intake_date`),
  KEY `idx_animals_species_status` (`species`, `status`),
  KEY `idx_animals_is_deleted` (`is_deleted`),
  CONSTRAINT `fk_animals_breed` FOREIGN KEY (`breed_id`) REFERENCES `breeds` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_animals_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_animals_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animal_photos` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` BIGINT UNSIGNED NOT NULL,
  `file_path` VARCHAR(500) NOT NULL,
  `file_name` VARCHAR(255) NOT NULL,
  `file_size_bytes` INT UNSIGNED NOT NULL,
  `mime_type` VARCHAR(50) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Main display photo',
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uploaded_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_photos_animal` (`animal_id`),
  CONSTRAINT `fk_photos_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_photos_uploaded_by` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `animal_qr_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` BIGINT UNSIGNED NOT NULL,
  `qr_data` VARCHAR(500) NOT NULL COMMENT 'Encoded URL or data',
  `file_path` VARCHAR(500) NOT NULL COMMENT 'Path to generated QR PNG',
  `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_qr_animal` (`animal_id`),
  CONSTRAINT `fk_qr_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_qr_generated_by` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 3. KENNEL MANAGEMENT
-- ============================================================================

CREATE TABLE IF NOT EXISTS `kennels` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kennel_code` VARCHAR(20) NOT NULL COMMENT 'e.g., K-A01, K-B12',
  `zone` VARCHAR(50) NOT NULL COMMENT 'e.g., Building A, Building B',
  `row_number` VARCHAR(10) NULL,
  `size_category` VARCHAR(20) NOT NULL COMMENT 'Small, Medium, Large, Extra Large',
  `type` VARCHAR(20) NOT NULL DEFAULT 'Indoor' COMMENT 'Indoor, Outdoor',
  `allowed_species` VARCHAR(20) NOT NULL DEFAULT 'Dog' COMMENT 'Dog, Cat, Any',
  `max_occupants` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Available' COMMENT 'Available, Occupied, Maintenance, Quarantine',
  `notes` TEXT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_kennels_code` (`kennel_code`),
  KEY `idx_kennels_status` (`status`),
  KEY `idx_kennels_species_size` (`allowed_species`, `size_category`),
  KEY `idx_kennels_zone` (`zone`),
  CONSTRAINT `fk_kennels_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kennel_assignments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kennel_id` BIGINT UNSIGNED NOT NULL,
  `animal_id` BIGINT UNSIGNED NOT NULL,
  `assigned_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `released_at` DATETIME NULL COMMENT 'NULL = currently assigned',
  `transfer_reason` VARCHAR(255) NULL,
  `assigned_by` BIGINT UNSIGNED NULL,
  `released_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_kennel_assign_kennel` (`kennel_id`),
  KEY `idx_kennel_assign_animal` (`animal_id`),
  KEY `idx_kennel_assign_active` (`kennel_id`, `released_at`),
  CONSTRAINT `fk_kennel_assign_kennel` FOREIGN KEY (`kennel_id`) REFERENCES `kennels` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_kennel_assign_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_kennel_assign_by` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `kennel_maintenance_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `kennel_id` BIGINT UNSIGNED NOT NULL,
  `maintenance_type` VARCHAR(50) NOT NULL COMMENT 'Cleaning, Repair, Sanitization, Inspection',
  `description` TEXT NULL,
  `scheduled_date` DATE NULL,
  `completed_at` DATETIME NULL,
  `performed_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_maintenance_kennel` (`kennel_id`),
  KEY `idx_maintenance_scheduled` (`scheduled_date`),
  CONSTRAINT `fk_maintenance_kennel` FOREIGN KEY (`kennel_id`) REFERENCES `kennels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_maintenance_by` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 4. MEDICAL RECORDS (Base + Sub-type tables for atomic design)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `medical_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `animal_id` BIGINT UNSIGNED NOT NULL,
  `procedure_type` VARCHAR(30) NOT NULL COMMENT 'vaccination, surgery, examination, treatment, deworming, euthanasia',
  `record_date` DATETIME NOT NULL,
  `general_notes` TEXT NULL,
  `veterinarian_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK to users with vet role',
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_medical_animal` (`animal_id`),
  KEY `idx_medical_type` (`procedure_type`),
  KEY `idx_medical_date` (`record_date`),
  KEY `idx_medical_animal_date` (`animal_id`, `record_date` DESC),
  KEY `idx_medical_vet` (`veterinarian_id`),
  KEY `idx_medical_is_deleted` (`is_deleted`),
  CONSTRAINT `fk_medical_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_medical_vet` FOREIGN KEY (`veterinarian_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `vaccination_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `vaccine_name` VARCHAR(100) NOT NULL COMMENT 'Anti-rabies, DHPP, FVRCP, etc.',
  `vaccine_brand` VARCHAR(100) NULL,
  `batch_lot_number` VARCHAR(50) NULL,
  `dosage_ml` DECIMAL(5,2) NOT NULL,
  `route` VARCHAR(30) NOT NULL COMMENT 'Subcutaneous, Intramuscular, Oral',
  `injection_site` VARCHAR(50) NULL COMMENT 'Left shoulder, Right hip, etc.',
  `dose_number` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `next_due_date` DATE NULL,
  `adverse_reactions` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vaccination_medical` (`medical_record_id`),
  KEY `idx_vaccination_next_due` (`next_due_date`),
  CONSTRAINT `fk_vaccination_medical` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `surgery_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `surgery_type` VARCHAR(50) NOT NULL COMMENT 'Spay, Neuter, Tumor Removal, Amputation, Wound Repair, Other',
  `pre_op_weight_kg` DECIMAL(5,2) NULL,
  `anesthesia_type` VARCHAR(30) NOT NULL COMMENT 'General, Local, Sedation',
  `anesthesia_drug` VARCHAR(100) NULL,
  `anesthesia_dosage` VARCHAR(50) NULL,
  `duration_minutes` INT UNSIGNED NULL,
  `surgical_notes` TEXT NULL,
  `complications` TEXT NULL,
  `post_op_instructions` TEXT NULL,
  `follow_up_date` DATE NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_surgery_medical` (`medical_record_id`),
  CONSTRAINT `fk_surgery_medical` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `examination_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `weight_kg` DECIMAL(5,2) NULL,
  `temperature_celsius` DECIMAL(4,1) NULL,
  `heart_rate_bpm` INT UNSIGNED NULL,
  `respiratory_rate` INT UNSIGNED NULL,
  `body_condition_score` TINYINT UNSIGNED NULL COMMENT '1-9 BCS scale',
  `eyes_status` VARCHAR(20) NULL COMMENT 'Normal, Abnormal',
  `eyes_notes` TEXT NULL,
  `ears_status` VARCHAR(20) NULL,
  `ears_notes` TEXT NULL,
  `teeth_gums_status` VARCHAR(20) NULL,
  `teeth_gums_notes` TEXT NULL,
  `skin_coat_status` VARCHAR(20) NULL,
  `skin_coat_notes` TEXT NULL,
  `musculoskeletal_status` VARCHAR(20) NULL,
  `musculoskeletal_notes` TEXT NULL,
  `overall_assessment` TEXT NULL,
  `recommendations` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_examination_medical` (`medical_record_id`),
  CONSTRAINT `fk_examination_medical` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `treatment_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `diagnosis` VARCHAR(255) NOT NULL,
  `medication_name` VARCHAR(150) NOT NULL,
  `dosage` VARCHAR(100) NOT NULL COMMENT 'e.g., 250mg twice daily',
  `route` VARCHAR(30) NOT NULL COMMENT 'Oral, Injection, Topical, IV',
  `frequency` VARCHAR(50) NOT NULL COMMENT 'Once daily, Twice daily, etc.',
  `duration_days` INT UNSIGNED NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NULL,
  `quantity_dispensed` INT UNSIGNED NULL,
  `inventory_item_id` BIGINT UNSIGNED NULL COMMENT 'FK for auto-deduction',
  `special_instructions` TEXT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_treatment_medical` (`medical_record_id`),
  KEY `idx_treatment_inventory` (`inventory_item_id`),
  CONSTRAINT `fk_treatment_medical` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_treatment_inventory` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `deworming_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `dewormer_name` VARCHAR(100) NOT NULL,
  `brand` VARCHAR(100) NULL,
  `dosage` VARCHAR(100) NOT NULL,
  `weight_at_treatment_kg` DECIMAL(5,2) NULL,
  `next_due_date` DATE NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_deworming_medical` (`medical_record_id`),
  KEY `idx_deworming_next_due` (`next_due_date`),
  CONSTRAINT `fk_deworming_medical` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `euthanasia_records` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `reason_category` VARCHAR(50) NOT NULL COMMENT 'Medical, Behavioral, Legal/Court Order, Population Management',
  `reason_details` TEXT NOT NULL,
  `authorized_by` BIGINT UNSIGNED NOT NULL COMMENT 'Authorizing officer',
  `method` VARCHAR(50) NOT NULL COMMENT 'IV Injection (Pentobarbital), etc.',
  `drug_used` VARCHAR(100) NULL,
  `drug_dosage` VARCHAR(50) NULL,
  `time_of_death` DATETIME NOT NULL,
  `death_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `disposal_method` VARCHAR(30) NOT NULL COMMENT 'Cremation, Burial',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_euthanasia_medical` (`medical_record_id`),
  CONSTRAINT `fk_euthanasia_medical` FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_euthanasia_authorized` FOREIGN KEY (`authorized_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 5. ADOPTION MANAGEMENT
-- ============================================================================

CREATE TABLE IF NOT EXISTS `adoption_applications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_number` VARCHAR(20) NOT NULL COMMENT 'APP-YYYY-NNNN',
  `adopter_id` BIGINT UNSIGNED NOT NULL COMMENT 'FK to users (adopter role)',
  `animal_id` BIGINT UNSIGNED NULL COMMENT 'Specific animal if selected',
  `status` VARCHAR(30) NOT NULL DEFAULT 'pending_review' COMMENT 'pending_review, interview_scheduled, interview_completed, seminar_scheduled, seminar_completed, pending_payment, completed, rejected, withdrawn',
  `preferred_species` VARCHAR(20) NULL,
  `preferred_breed` VARCHAR(100) NULL,
  `preferred_age_min` TINYINT UNSIGNED NULL,
  `preferred_age_max` TINYINT UNSIGNED NULL,
  `preferred_size` VARCHAR(20) NULL,
  `preferred_gender` VARCHAR(10) NULL,
  `housing_type` VARCHAR(30) NULL COMMENT 'House, Apartment, Condo',
  `housing_ownership` VARCHAR(20) NULL COMMENT 'Owned, Rented',
  `has_yard` TINYINT(1) NULL,
  `yard_size` VARCHAR(50) NULL,
  `num_adults` TINYINT UNSIGNED NULL,
  `num_children` TINYINT UNSIGNED NULL,
  `children_ages` VARCHAR(100) NULL,
  `existing_pets_description` TEXT NULL,
  `previous_pet_experience` TEXT NULL,
  `vet_reference_name` VARCHAR(200) NULL,
  `vet_reference_clinic` VARCHAR(200) NULL,
  `vet_reference_contact` VARCHAR(20) NULL,
  `valid_id_path` VARCHAR(2000) NULL COMMENT 'Uploaded ID document(s) as JSON',
  `digital_signature_path` VARCHAR(500) NULL,
  `agrees_to_policies` TINYINT(1) NOT NULL DEFAULT 0,
  `agrees_to_home_visit` TINYINT(1) NOT NULL DEFAULT 0,
  `agrees_to_return_policy` TINYINT(1) NOT NULL DEFAULT 0,
  `rejection_reason` TEXT NULL,
  `withdrawn_reason` TEXT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_applications_number` (`application_number`),
  KEY `idx_applications_adopter` (`adopter_id`),
  KEY `idx_applications_animal` (`animal_id`),
  KEY `idx_applications_status` (`status`),
  KEY `idx_applications_status_date` (`status`, `created_at` DESC),
  CONSTRAINT `fk_applications_adopter` FOREIGN KEY (`adopter_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_applications_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `adoption_interviews` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` BIGINT UNSIGNED NOT NULL,
  `scheduled_date` DATETIME NOT NULL,
  `interview_type` VARCHAR(20) NOT NULL DEFAULT 'in_person' COMMENT 'in_person, video_call',
  `video_call_link` VARCHAR(500) NULL,
  `location` VARCHAR(255) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'scheduled' COMMENT 'scheduled, completed, cancelled, no_show',
  `screening_checklist` JSON NULL COMMENT 'Array of {question, answer, passed}',
  `home_assessment_notes` TEXT NULL,
  `pet_care_knowledge_score` TINYINT UNSIGNED NULL COMMENT '1-10',
  `overall_recommendation` VARCHAR(20) NULL COMMENT 'Approve, Conditional, Reject',
  `interviewer_notes` TEXT NULL,
  `conducted_by` BIGINT UNSIGNED NULL,
  `completed_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_interviews_application` (`application_id`),
  KEY `idx_interviews_date` (`scheduled_date`),
  KEY `idx_interviews_status` (`status`),
  CONSTRAINT `fk_interviews_application` FOREIGN KEY (`application_id`) REFERENCES `adoption_applications` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_interviews_conducted_by` FOREIGN KEY (`conducted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `adoption_seminars` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `scheduled_date` DATETIME NOT NULL,
  `end_time` DATETIME NULL,
  `location` VARCHAR(255) NOT NULL,
  `capacity` INT UNSIGNED NOT NULL DEFAULT 20,
  `facilitator_id` BIGINT UNSIGNED NULL,
  `description` TEXT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'scheduled' COMMENT 'scheduled, in_progress, completed, cancelled',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_seminars_date` (`scheduled_date`),
  KEY `idx_seminars_status` (`status`),
  CONSTRAINT `fk_seminars_facilitator` FOREIGN KEY (`facilitator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `seminar_attendees` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `seminar_id` BIGINT UNSIGNED NOT NULL,
  `application_id` BIGINT UNSIGNED NOT NULL,
  `attendance_status` VARCHAR(20) NOT NULL DEFAULT 'registered' COMMENT 'registered, attended, no_show',
  `marked_by` BIGINT UNSIGNED NULL,
  `marked_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_attendees_seminar_app` (`seminar_id`, `application_id`),
  KEY `idx_attendees_application` (`application_id`),
  CONSTRAINT `fk_attendees_seminar` FOREIGN KEY (`seminar_id`) REFERENCES `adoption_seminars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_attendees_application` FOREIGN KEY (`application_id`) REFERENCES `adoption_applications` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `adoption_completions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `application_id` BIGINT UNSIGNED NOT NULL,
  `animal_id` BIGINT UNSIGNED NOT NULL,
  `adopter_id` BIGINT UNSIGNED NOT NULL,
  `completion_date` DATETIME NOT NULL,
  `payment_confirmed` TINYINT(1) NOT NULL DEFAULT 0,
  `contract_signed` TINYINT(1) NOT NULL DEFAULT 0,
  `contract_signature_path` VARCHAR(500) NULL,
  `medical_records_provided` TINYINT(1) NOT NULL DEFAULT 0,
  `spay_neuter_agreement` TINYINT(1) NOT NULL DEFAULT 0,
  `certificate_path` VARCHAR(500) NULL COMMENT 'Generated adoption certificate PDF',
  `notes` TEXT NULL,
  `processed_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_completions_application` (`application_id`),
  KEY `idx_completions_animal` (`animal_id`),
  KEY `idx_completions_adopter` (`adopter_id`),
  KEY `idx_completions_date` (`completion_date`),
  CONSTRAINT `fk_completions_application` FOREIGN KEY (`application_id`) REFERENCES `adoption_applications` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_completions_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_completions_adopter` FOREIGN KEY (`adopter_id`) REFERENCES `users` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_completions_processed_by` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 6. BILLING & INVOICING
-- ============================================================================

CREATE TABLE IF NOT EXISTS `fee_schedule` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(50) NOT NULL COMMENT 'Adoption, Surrender, Impound, Medical, License, Fine',
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `is_per_day` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = daily rate (e.g., impound)',
  `species_filter` VARCHAR(20) NULL COMMENT 'NULL = all species',
  `effective_from` DATE NOT NULL,
  `effective_to` DATE NULL COMMENT 'NULL = currently active',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fee_category` (`category`),
  KEY `idx_fee_active` (`is_active`, `effective_from`),
  CONSTRAINT `fk_fee_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoices` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_number` VARCHAR(20) NOT NULL COMMENT 'INV-YYYY-NNNN',
  `payor_type` VARCHAR(20) NOT NULL DEFAULT 'adopter' COMMENT 'adopter, owner, external',
  `payor_user_id` BIGINT UNSIGNED NULL COMMENT 'FK if payor is a system user',
  `payor_name` VARCHAR(200) NOT NULL,
  `payor_contact` VARCHAR(20) NULL,
  `payor_address` VARCHAR(500) NULL,
  `animal_id` BIGINT UNSIGNED NULL COMMENT 'Related animal if applicable',
  `application_id` BIGINT UNSIGNED NULL COMMENT 'Related adoption if applicable',
  `subtotal` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount_paid` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `balance_due` DECIMAL(10,2) GENERATED ALWAYS AS (`total_amount` - `amount_paid`) STORED,
  `payment_status` VARCHAR(20) NOT NULL DEFAULT 'unpaid' COMMENT 'unpaid, partial, paid, void',
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `notes` TEXT NULL,
  `terms` TEXT NULL,
  `pdf_path` VARCHAR(500) NULL,
  `voided_at` DATETIME NULL,
  `voided_reason` TEXT NULL,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_invoices_number` (`invoice_number`),
  KEY `idx_invoices_status` (`payment_status`),
  KEY `idx_invoices_date` (`issue_date`),
  KEY `idx_invoices_payor` (`payor_user_id`),
  KEY `idx_invoices_animal` (`animal_id`),
  KEY `idx_invoices_status_date` (`payment_status`, `issue_date` DESC),
  CONSTRAINT `fk_invoices_payor` FOREIGN KEY (`payor_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_invoices_animal` FOREIGN KEY (`animal_id`) REFERENCES `animals` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_invoices_application` FOREIGN KEY (`application_id`) REFERENCES `adoption_applications` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_invoices_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `invoice_line_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `fee_schedule_id` BIGINT UNSIGNED NULL COMMENT 'FK to fee_schedule if from standard fee',
  `description` VARCHAR(500) NOT NULL,
  `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL,
  `total_price` DECIMAL(10,2) GENERATED ALWAYS AS (`quantity` * `unit_price`) STORED,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_line_items_invoice` (`invoice_id`),
  CONSTRAINT `fk_line_items_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_line_items_fee` FOREIGN KEY (`fee_schedule_id`) REFERENCES `fee_schedule` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice_id` BIGINT UNSIGNED NOT NULL,
  `payment_number` VARCHAR(20) NOT NULL COMMENT 'PAY-YYYY-NNNN',
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_method` VARCHAR(30) NOT NULL COMMENT 'Cash, Bank Transfer, GCash, Maya, Check',
  `reference_number` VARCHAR(100) NULL COMMENT 'For digital/check payments',
  `payment_date` DATETIME NOT NULL,
  `receipt_number` VARCHAR(50) NULL,
  `receipt_path` VARCHAR(500) NULL COMMENT 'Generated receipt PDF',
  `notes` TEXT NULL,
  `voided_at` DATETIME NULL,
  `voided_reason` TEXT NULL,
  `received_by` BIGINT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_payments_number` (`payment_number`),
  KEY `idx_payments_invoice` (`invoice_id`),
  KEY `idx_payments_date` (`payment_date`),
  KEY `idx_payments_method` (`payment_method`),
  CONSTRAINT `fk_payments_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_payments_received_by` FOREIGN KEY (`received_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 7. INVENTORY MANAGEMENT
-- ============================================================================

CREATE TABLE IF NOT EXISTS `inventory_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL COMMENT 'Medical Supplies, Food & Nutrition, Cleaning, Office Supplies, Equipment',
  `description` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inv_categories_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `inventory_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sku` VARCHAR(50) NOT NULL,
  `name` VARCHAR(200) NOT NULL,
  `category_id` BIGINT UNSIGNED NOT NULL,
  `unit_of_measure` VARCHAR(30) NOT NULL COMMENT 'pcs, ml, mg, kg, box, pack, bottle',
  `cost_per_unit` DECIMAL(10,2) NULL,
  `supplier_name` VARCHAR(200) NULL,
  `supplier_contact` VARCHAR(100) NULL,
  `reorder_level` INT UNSIGNED NOT NULL DEFAULT 10,
  `quantity_on_hand` INT NOT NULL DEFAULT 0,
  `storage_location` VARCHAR(100) NULL COMMENT 'Shelf, room, etc.',
  `expiry_date` DATE NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `is_deleted` TINYINT(1) NOT NULL DEFAULT 0,
  `deleted_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` BIGINT UNSIGNED NULL,
  `updated_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventory_sku` (`sku`),
  KEY `idx_inventory_category` (`category_id`),
  KEY `idx_inventory_stock` (`quantity_on_hand`),
  KEY `idx_inventory_expiry` (`expiry_date`),
  KEY `idx_inventory_active` (`is_active`, `is_deleted`),
  KEY `idx_inventory_name` (`name`),
  CONSTRAINT `fk_inventory_category` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `stock_transactions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `inventory_item_id` BIGINT UNSIGNED NOT NULL,
  `transaction_type` VARCHAR(20) NOT NULL COMMENT 'stock_in, stock_out, adjustment',
  `quantity` INT NOT NULL COMMENT 'Positive for in, negative for out',
  `quantity_before` INT NOT NULL,
  `quantity_after` INT NOT NULL,
  `reason` VARCHAR(50) NOT NULL COMMENT 'purchase, donation, return, usage, dispensed, wastage, transfer, count_correction',
  `reference_type` VARCHAR(30) NULL COMMENT 'medical_record, animal, invoice, manual',
  `reference_id` BIGINT UNSIGNED NULL COMMENT 'FK to related record',
  `batch_lot_number` VARCHAR(50) NULL,
  `expiry_date` DATE NULL,
  `source_supplier` VARCHAR(200) NULL COMMENT 'For stock_in',
  `notes` TEXT NULL,
  `transacted_by` BIGINT UNSIGNED NOT NULL,
  `transacted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stock_item` (`inventory_item_id`),
  KEY `idx_stock_type` (`transaction_type`),
  KEY `idx_stock_date` (`transacted_at`),
  KEY `idx_stock_reference` (`reference_type`, `reference_id`),
  CONSTRAINT `fk_stock_item` FOREIGN KEY (`inventory_item_id`) REFERENCES `inventory_items` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_stock_transacted_by` FOREIGN KEY (`transacted_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- 8. SYSTEM & AUDIT
-- ============================================================================

CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NULL,
  `action` VARCHAR(20) NOT NULL COMMENT 'create, update, delete, restore, login, logout, failed_login',
  `module` VARCHAR(50) NOT NULL COMMENT 'animals, medical, adoptions, billing, inventory, users, auth, settings',
  `record_table` VARCHAR(100) NULL,
  `record_id` BIGINT UNSIGNED NULL,
  `old_values` JSON NULL COMMENT 'Previous state (for updates)',
  `new_values` JSON NULL COMMENT 'New state (for creates/updates)',
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(500) NULL,
  `request_id` VARCHAR(36) NULL COMMENT 'UUID for request correlation',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_module` (`module`),
  KEY `idx_audit_action` (`action`),
  KEY `idx_audit_date` (`created_at`),
  KEY `idx_audit_record` (`record_table`, `record_id`),
  KEY `idx_audit_request` (`request_id`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL COMMENT 'Recipient',
  `type` VARCHAR(50) NOT NULL COMMENT 'adoption_application, interview_scheduled, low_stock, medical_followup, system',
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `link` VARCHAR(500) NULL COMMENT 'URL to related page',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `read_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_notifications_user` (`user_id`),
  KEY `idx_notifications_unread` (`user_id`, `is_read`),
  KEY `idx_notifications_type` (`type`),
  KEY `idx_notifications_date` (`created_at`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_backups` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `backup_type` VARCHAR(20) NOT NULL COMMENT 'full, incremental',
  `file_path` VARCHAR(500) NOT NULL,
  `file_size_bytes` BIGINT UNSIGNED NOT NULL,
  `checksum_sha256` VARCHAR(64) NOT NULL COMMENT 'Integrity verification',
  `status` VARCHAR(20) NOT NULL DEFAULT 'completed' COMMENT 'in_progress, completed, failed, restored',
  `tables_included` JSON NULL,
  `error_message` TEXT NULL,
  `started_at` DATETIME NOT NULL,
  `completed_at` DATETIME NULL,
  `restored_at` DATETIME NULL,
  `created_by` BIGINT UNSIGNED NULL,
  `restored_by` BIGINT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  KEY `idx_backups_status` (`status`),
  KEY `idx_backups_date` (`started_at`),
  CONSTRAINT `fk_backups_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` JSON NOT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `report_templates` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(200) NOT NULL,
  `report_type` VARCHAR(50) NOT NULL COMMENT 'animal_intake, medical, adoption, billing, inventory, audit, census',
  `configuration` JSON NOT NULL COMMENT '{columns, filters, grouping, date_range, sort}',
  `is_system` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = built-in, cannot delete',
  `created_by` BIGINT UNSIGNED NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report_type` (`report_type`),
  KEY `idx_report_creator` (`created_by`),
  CONSTRAINT `fk_report_creator` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rate_limit_attempts` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `key` VARCHAR(255) NOT NULL COMMENT 'IP:endpoint or user_id:endpoint',
  `attempts` INT UNSIGNED NOT NULL DEFAULT 1,
  `window_start` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rate_limit_key` (`key`),
  KEY `idx_rate_limit_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `id_sequences` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `sequence_key` VARCHAR(50) NOT NULL COMMENT 'animal_id, invoice_number, application_number, payment_number',
  `prefix` VARCHAR(10) NOT NULL COMMENT 'A, INV, APP, PAY',
  `current_year` YEAR NOT NULL,
  `last_number` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_sequences_key_year` (`sequence_key`, `current_year`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- SCHEMA SUMMARY
-- ============================================================================
-- Total Tables: 38
--
-- Auth & Users (6):       roles, permissions, role_permissions, users,
--                         user_sessions, password_reset_tokens
--
-- Animals (4):            breeds, animals, animal_photos, animal_qr_codes
--
-- Kennels (3):            kennels, kennel_assignments, kennel_maintenance_logs
--
-- Medical (7):            medical_records, vaccination_records, surgery_records,
--                         examination_records, treatment_records,
--                         deworming_records, euthanasia_records
--
-- Adoptions (5):          adoption_applications, adoption_interviews,
--                         adoption_seminars, seminar_attendees,
--                         adoption_completions
--
-- Billing (4):            fee_schedule, invoices, invoice_line_items, payments
--
-- Inventory (3):          inventory_categories, inventory_items,
--                         stock_transactions
--
-- System (7):             audit_logs, notifications, system_backups,
--                         system_settings, report_templates,
--                         rate_limit_attempts, id_sequences
-- ============================================================================
