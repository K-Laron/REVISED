<?php

declare(strict_types=1);

namespace App\Services\Reports;

use App\Models\AdoptionApplication;
use App\Models\AdoptionCompletion;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AnimalService;

final class AnimalDossierService
{
    public function __construct(
        private readonly AnimalService $animals,
        private readonly AdoptionApplication $adoptions,
        private readonly AdoptionCompletion $completions,
        private readonly Invoice $invoices,
        private readonly Payment $payments,
        private readonly AuditLog $audit
    ) {
    }

    public function assemble(int $animalId): array
    {
        $animal = $this->animals->get((string) $animalId, true);

        $animal['adoption_application'] = $this->adoptions->findLatestByAnimal($animalId) ?: null;
        $animal['adoption_completion'] = $this->completions->findByAnimal($animalId) ?: null;
        $animal['invoices'] = $this->invoices->listByAnimal($animalId);
        $animal['payments'] = $this->payments->listByAnimalAcrossInvoices($animalId);
        $animal['audit_trail'] = $this->audit->listForAnimalDossier($animalId);

        foreach ($animal['audit_trail'] as &$entry) {
            $entry['old_values'] = $entry['old_values'] ? json_decode((string) $entry['old_values'], true) : [];
            $entry['new_values'] = $entry['new_values'] ? json_decode((string) $entry['new_values'], true) : [];
        }
        unset($entry);

        return $animal;
    }
}
