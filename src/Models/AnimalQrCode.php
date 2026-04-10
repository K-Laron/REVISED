<?php

declare(strict_types=1);

namespace App\Models;

class AnimalQrCode extends BaseModel
{
    protected static string $table = 'animal_qr_codes';
    protected static bool $useSoftDeletes = false; // QR codes are replaced or physically deleted

    public function findByAnimal(int $animalId): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM animal_qr_codes WHERE animal_id = :animal_id ORDER BY generated_at DESC LIMIT 1',
            ['animal_id' => $animalId]
        );
    }

    public function findByQrData(string $qrData): array|false
    {
        return $this->db->fetch(
            'SELECT * FROM animal_qr_codes WHERE qr_data = :qr_data LIMIT 1',
            ['qr_data' => $qrData]
        );
    }

    public function replaceQr(int $animalId, string $qrData, string $filePath, ?int $generatedBy): void
    {
        $this->db->execute('DELETE FROM animal_qr_codes WHERE animal_id = :animal_id', ['animal_id' => $animalId]);
        
        $this->create([
            'animal_id' => $animalId,
            'qr_data' => $qrData,
            'file_path' => $filePath,
            'generated_by' => $generatedBy,
        ]);
    }
}
