-- Migration: Add medical lab results table
-- Date: 2026-03-26

CREATE TABLE IF NOT EXISTS `medical_lab_results` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `test_name` VARCHAR(200) NOT NULL,
  `result_value` VARCHAR(255) NULL,
  `normal_range` VARCHAR(100) NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'Pending' COMMENT 'Pending, Normal, Abnormal',
  `date_conducted` DATE NULL,
  `remarks` TEXT NULL,
  `attachment_path` VARCHAR(500) NULL,
  `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_lab_results_medical` (`medical_record_id`),
  CONSTRAINT `fk_lab_results_medical` FOREIGN KEY (`medical_record_id`)
    REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
