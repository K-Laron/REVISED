<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class AnimalQrCode
{
    public function findByAnimal(int $animalId): array|false
    {
        return Database::fetch(
            'SELECT * FROM animal_qr_codes WHERE animal_id = :animal_id ORDER BY generated_at DESC LIMIT 1',
            ['animal_id' => $animalId]
        );
    }

    public function findByQrData(string $qrData): array|false
    {
        return Database::fetch(
            'SELECT * FROM animal_qr_codes WHERE qr_data = :qr_data LIMIT 1',
            ['qr_data' => $qrData]
        );
    }

    public function replace(int $animalId, string $qrData, string $filePath, ?int $generatedBy): void
    {
        Database::execute('DELETE FROM animal_qr_codes WHERE animal_id = :animal_id', ['animal_id' => $animalId]);
        Database::execute(
            'INSERT INTO animal_qr_codes (animal_id, qr_data, file_path, generated_by)
             VALUES (:animal_id, :qr_data, :file_path, :generated_by)',
            [
                'animal_id' => $animalId,
                'qr_data' => $qrData,
                'file_path' => $filePath,
                'generated_by' => $generatedBy,
            ]
        );
    }
}
