ALTER TABLE kennels
    ADD INDEX idx_kennels_deleted_status (is_deleted, status);

ALTER TABLE animals
    ADD INDEX idx_animals_deleted_intake (is_deleted, intake_date);

ALTER TABLE adoption_applications
    ADD INDEX idx_applications_deleted_created (is_deleted, created_at, id);

ALTER TABLE medical_records
    ADD INDEX idx_medical_deleted_record_date (is_deleted, record_date, id, animal_id);

ALTER TABLE invoices
    ADD INDEX idx_invoices_deleted_created (is_deleted, created_at, id, animal_id);

ALTER TABLE users
    ADD INDEX idx_users_deleted_created_role (is_deleted, created_at, id, role_id);
