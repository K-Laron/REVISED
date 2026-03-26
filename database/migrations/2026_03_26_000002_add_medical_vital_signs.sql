-- Migration: Add medical vital signs table
-- Date: 2026-03-26

CREATE TABLE IF NOT EXISTS `medical_vital_signs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `medical_record_id` BIGINT UNSIGNED NOT NULL,
  `weight_kg` DECIMAL(5,2) NULL,
  `temperature_celsius` DECIMAL(4,1) NULL,
  `heart_rate_bpm` INT UNSIGNED NULL,
  `respiratory_rate` INT UNSIGNED NULL,
  `body_condition_score` TINYINT UNSIGNED NULL COMMENT '1-9 scale',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_vital_signs_medical` (`medical_record_id`),
  CONSTRAINT `fk_vital_signs_medical` FOREIGN KEY (`medical_record_id`)
    REFERENCES `medical_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
