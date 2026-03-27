<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Animal;
use App\Models\AnimalQrCode;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use RuntimeException;

class QrCodeService
{
    private AnimalQrCode $qrCodes;
    private Animal $animals;

    public function __construct()
    {
        $this->qrCodes = new AnimalQrCode();
        $this->animals = new Animal();
    }

    public function generateForAnimal(int $animalId, string $animalCode, ?int $generatedBy = null): array
    {
        $qrData = $this->encodePayload([
            'animal_id' => $animalCode,
            'url' => rtrim((string) ($_ENV['APP_URL'] ?? 'http://localhost:8000'), '/') . '/animals/' . $animalId,
        ]);

        $directory = dirname(__DIR__, 2) . '/public/uploads/qrcodes';
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $usesRaster = extension_loaded('gd');
        $extension = $usesRaster ? 'png' : 'svg';
        $relativePath = 'uploads/qrcodes/' . $animalCode . '.' . $extension;
        $absolutePath = dirname(__DIR__, 2) . '/public/' . $relativePath;

        $options = new QROptions([
            'outputType' => $usesRaster ? QRCode::OUTPUT_IMAGE_PNG : QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_M,
            'scale' => 10,
            'imageBase64' => false,
            'addQuietzone' => true,
            'quietzoneSize' => 4,
            'svgAddXmlHeader' => true,
            'svgWidth' => 300,
            'svgHeight' => 300,
            'connectPaths' => true, // Optimize SVG size
        ]);

        $output = (new QRCode($options))->render($qrData);
        
        if (file_put_contents($absolutePath, $output) === false) {
            throw new RuntimeException('Failed to write QR code file to: ' . $absolutePath);
        }

        $this->qrCodes->replace($animalId, $qrData, $relativePath, $generatedBy);

        $record = $this->qrCodes->findByAnimal($animalId);
        if ($record === false) {
            throw new RuntimeException('QR code record was not created.');
        }

        return $record;
    }

    public function getOrGenerate(int $animalId): array
    {
        $record = $this->qrCodes->findByAnimal($animalId);
        if ($record !== false) {
            $absolutePath = dirname(__DIR__, 2) . '/public/' . $record['file_path'];
            if (is_file($absolutePath)) {
                return $record;
            }
        }

        $animal = $this->animals->find($animalId);
        if ($animal === false) {
            throw new RuntimeException('Animal not found for QR generation.');
        }

        return $this->generateForAnimal((int) $animal['id'], (string) $animal['animal_id']);
    }

    public function resolveScan(string $qrData): array|false
    {
        $payload = $this->decodePayload($qrData);
        if ($payload === null) {
            return false;
        }

        if (isset($payload['animal_id'])) {
            return $this->animals->find((string) $payload['animal_id']);
        }

        return false;
    }

    private function encodePayload(array $payload): string
    {
        return rtrim(strtr(base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES)), '+/', '-_'), '=');
    }

    private function decodePayload(string $encoded): ?array
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'), true);
        if ($decoded === false) {
            return null;
        }

        $payload = json_decode($decoded, true);
        return is_array($payload) ? $payload : null;
    }
}
