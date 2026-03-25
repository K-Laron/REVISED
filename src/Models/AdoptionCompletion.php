<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class AdoptionCompletion
{
    public function findByApplication(int $applicationId): array|false
    {
        return Database::fetch(
            'SELECT ac.*,
                    CONCAT(u.first_name, " ", u.last_name) AS processed_by_name
             FROM adoption_completions ac
             LEFT JOIN users u ON u.id = ac.processed_by
             WHERE ac.application_id = :application_id
             LIMIT 1',
            ['application_id' => $applicationId]
        );
    }

    public function create(array $data): int
    {
        Database::execute(
            'INSERT INTO adoption_completions (
                application_id, animal_id, adopter_id, completion_date, payment_confirmed, contract_signed,
                contract_signature_path, medical_records_provided, spay_neuter_agreement,
                certificate_path, notes, processed_by
             ) VALUES (
                :application_id, :animal_id, :adopter_id, :completion_date, :payment_confirmed, :contract_signed,
                :contract_signature_path, :medical_records_provided, :spay_neuter_agreement,
                :certificate_path, :notes, :processed_by
             )',
            $data
        );

        return (int) Database::lastInsertId();
    }

    public function updateCertificatePath(int $id, string $certificatePath): void
    {
        Database::execute(
            'UPDATE adoption_completions
             SET certificate_path = :certificate_path
             WHERE id = :id',
            [
                'id' => $id,
                'certificate_path' => $certificatePath,
            ]
        );
    }
}
