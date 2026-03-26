-- Migration: Add medical prescriptions table
-- Date: 2026-03-26

CREATE TABLE IF NOT EXISTS `medical_prescriptions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `medicine_name` VARCHAR(200) NOT NULL,
  `dosage` VARCHAR(100) NOT NULL,
  `frequency` VARCHAR(100) NOT NULL,
  `duration` VARCHAR(100) NULL,
  `quantity` INT UNSIGNED NULL,
  `instructions` TEXT NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_prescriptions_medical` (`medical_record_id`),
  CONSTRAINT `fk_prescriptions_medical` FOREIGN KEY (`medical_record_id`)
    REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
