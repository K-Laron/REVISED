<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Core\Database;
use App\Services\AnimalService;

final class AnimalDossierService
{
    /** @var callable(string, int): ?array */
    private $fetchSingle;

    /** @var callable(string, int): array */
    private $fetchMany;

    public function __construct(
        private readonly AnimalService $animals,
        ?callable $fetchSingle = null,
        ?callable $fetchMany = null
    ) {
        $this->fetchSingle = $fetchSingle ?? $this->defaultSingleFetcher();
        $this->fetchMany = $fetchMany ?? $this->defaultManyFetcher();
    }

    public function assemble(int $animalId): array
    {
        $animal = $this->animals->get((string) $animalId, true);

        $animal['adoption_application'] = ($this->fetchSingle)('application', $animalId);
        $animal['adoption_completion'] = ($this->fetchSingle)('completion', $animalId);
        $animal['invoices'] = ($this->fetchMany)('invoices', $animalId);
        $animal['payments'] = ($this->fetchMany)('payments', $animalId);
        $animal['audit_trail'] = ($this->fetchMany)('audit', $animalId);

        foreach ($animal['audit_trail'] as &$entry) {
            $entry['old_values'] = $entry['old_values'] ? json_decode((string) $entry['old_values'], true) : [];
            $entry['new_values'] = $entry['new_values'] ? json_decode((string) $entry['new_values'], true) : [];
        }
        unset($entry);

        return $animal;
    }

    private function defaultSingleFetcher(): callable
    {
        return static function (string $key, int $animalId): ?array {
            return match ($key) {
                'application' => Database::fetch(
                    'SELECT aa.*, CONCAT(u.first_name, " ", u.last_name) AS adopter_name
                     FROM adoption_applications aa
                     INNER JOIN users u ON u.id = aa.adopter_id
                     WHERE aa.animal_id = :animal_id
                       AND aa.is_deleted = 0
                     ORDER BY aa.created_at DESC
                     LIMIT 1',
                    ['animal_id' => $animalId]
                ) ?: null,
                'completion' => Database::fetch(
                    'SELECT ac.*, CONCAT(u.first_name, " ", u.last_name) AS processed_by_name
                     FROM adoption_completions ac
                     LEFT JOIN users u ON u.id = ac.processed_by
                     WHERE ac.animal_id = :animal_id
                     LIMIT 1',
                    ['animal_id' => $animalId]
                ) ?: null,
                default => null,
            };
        };
    }

    private function defaultManyFetcher(): callable
    {
        return static function (string $key, int $animalId): array {
            return match ($key) {
                'invoices' => Database::fetchAll(
                    'SELECT *
                     FROM invoices
                     WHERE animal_id = :animal_id
                       AND is_deleted = 0
                     ORDER BY issue_date DESC, id DESC',
                    ['animal_id' => $animalId]
                ),
                'payments' => Database::fetchAll(
                    'SELECT p.*, i.invoice_number
                     FROM payments p
                     INNER JOIN invoices i ON i.id = p.invoice_id
                     WHERE i.animal_id = :animal_id
                       AND i.is_deleted = 0
                     ORDER BY p.payment_date DESC, p.id DESC',
                    ['animal_id' => $animalId]
                ),
                'audit' => Database::fetchAll(
                    'SELECT al.*, CONCAT(u.first_name, " ", u.last_name) AS user_name
                     FROM audit_logs al
                     LEFT JOIN users u ON u.id = al.user_id
                     WHERE (al.record_table = "animals" AND al.record_id = :animal_id)
                        OR (al.record_table = "animal_photos" AND al.record_id = :animal_photo_record_id)
                     ORDER BY al.created_at DESC, al.id DESC',
                    ['animal_id' => $animalId, 'animal_photo_record_id' => $animalId]
                ),
                default => [],
            };
        };
    }
}
