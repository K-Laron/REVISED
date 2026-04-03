<?php

declare(strict_types=1);

namespace App\Services\Medical;

use App\Models\Animal;

final class MedicalAnimalStatusSynchronizer
{
    public function __construct(
        private readonly Animal $animals
    ) {
    }

    public function syncAfterWrite(string $type, int $animalId, array $detailPayload, int $userId): void
    {
        $animal = $this->animals->find($animalId);
        if ($animal === false) {
            return;
        }

        if ($type === 'euthanasia') {
            $this->animals->updateStatus(
                $animalId,
                'Deceased',
                'Euthanasia record added.',
                (string) $detailPayload['time_of_death'],
                $userId
            );

            return;
        }

        if (in_array($type, ['surgery', 'examination', 'treatment'], true) && !in_array((string) $animal['status'], ['Deceased', 'Adopted', 'Transferred'], true)) {
            $this->animals->updateStatus(
                $animalId,
                'Under Medical Care',
                ucfirst($type) . ' record added.',
                null,
                $userId
            );
        }
    }
}
