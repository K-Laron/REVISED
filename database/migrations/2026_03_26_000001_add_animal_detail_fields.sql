-- Migration: Add animal detail fields (panelist revision)
-- Date: 2026-03-26

ALTER TABLE `animals`
  ADD COLUMN `microchip_number` VARCHAR(50) NULL AFTER `temperament`,
  ADD COLUMN `spay_neuter_status` VARCHAR(20) NOT NULL DEFAULT 'Unknown' COMMENT 'Yes, No, Unknown' AFTER `microchip_number`,
  ADD COLUMN `vaccination_status_at_intake` VARCHAR(30) NOT NULL DEFAULT 'Unknown' COMMENT 'Up to date, Partial, None, Unknown' AFTER `condition_at_intake`,
  ADD COLUMN `special_needs_notes` TEXT NULL AFTER `distinguishing_features`,
  ADD COLUMN `impounding_officer_name` VARCHAR(200) NULL AFTER `brought_by_address`,
  ADD COLUMN `barangay_of_origin` VARCHAR(100) NULL AFTER `location_found`,
  ADD COLUMN `impoundment_order_number` VARCHAR(50) NULL AFTER `barangay_of_origin`,
  ADD COLUMN `authority_name` VARCHAR(200) NULL AFTER `impoundment_order_number`,
  ADD COLUMN `authority_position` VARCHAR(100) NULL AFTER `authority_name`,
  ADD COLUMN `authority_contact` VARCHAR(20) NULL AFTER `authority_position`;
