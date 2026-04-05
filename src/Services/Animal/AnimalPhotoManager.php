<?php

declare(strict_types=1);

namespace App\Services\Animal;

use App\Models\AnimalPhoto;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use RuntimeException;

final class AnimalPhotoManager
{
    private AnimalPhoto $photos;
    private string $publicRoot;
    private ?ImageManager $imageManager;

    public function __construct(
        ?AnimalPhoto $photos = null,
        ?string $publicRoot = null,
        ?ImageManager $imageManager = null
    ) {
        $this->photos = $photos ?? new AnimalPhoto();
        $this->publicRoot = rtrim($publicRoot ?? dirname(__DIR__, 3) . '/public', '/\\');
        $this->imageManager = $imageManager ?? (extension_loaded('gd') ? new ImageManager(new Driver()) : null);
    }

    public function upload(int $animalId, mixed $photoInput, int $userId): void
    {
        $files = $this->normalizeFiles($photoInput);
        if ($files === []) {
            return;
        }

        $directory = $this->publicRoot . '/uploads/animals/' . $animalId;
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $existingCount = $this->photos->countByAnimal($animalId);
        $canOptimize = $this->imageManager !== null;

        foreach ($files as $index => $file) {
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            if (!is_uploaded_file((string) $file['tmp_name']) && !is_file((string) $file['tmp_name'])) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
            $normalizedExtension = $extension === 'jpeg' ? 'jpg' : $extension;
            $storedExtension = $canOptimize ? 'jpg' : $normalizedExtension;
            $fileName = $this->generatePhotoFileName($directory, $storedExtension);
            $relativePath = 'uploads/animals/' . $animalId . '/' . $fileName;
            $absolutePath = $this->publicRoot . '/' . $relativePath;
            $mimeType = (string) ($file['type'] ?? 'application/octet-stream');

            if ($canOptimize && $this->imageManager !== null) {
                $image = $this->imageManager->read((string) $file['tmp_name']);
                $image->scaleDown(width: 1600, height: 1600);
                $image->toJpeg(85)->save($absolutePath);
                $mimeType = 'image/jpeg';
            } else {
                $this->movePhotoToStorage((string) $file['tmp_name'], $absolutePath);
                $mimeType = $this->detectMimeType($absolutePath, $mimeType);
            }

            $this->photos->create([
                'animal_id' => $animalId,
                'file_path' => $relativePath,
                'file_name' => $fileName,
                'file_size_bytes' => filesize($absolutePath) ?: 0,
                'mime_type' => $mimeType,
                'is_primary' => $existingCount === 0 && $index === 0 ? 1 : 0,
                'sort_order' => $existingCount + $index,
                'uploaded_by' => $userId,
            ]);
        }
    }

    public function delete(int $animalId, int $photoId): void
    {
        $photo = $this->photos->find($animalId, $photoId);
        if ($photo === false) {
            throw new RuntimeException('Photo not found.');
        }

        $absolutePath = $this->publicRoot . '/' . ltrim((string) $photo['file_path'], '/');
        if (is_file($absolutePath)) {
            unlink($absolutePath);
        }

        $this->photos->delete($photoId);
    }

    private function generatePhotoFileName(string $directory, string $extension): string
    {
        do {
            $fileName = 'animal-photo-' . bin2hex(random_bytes(16)) . '.' . $extension;
        } while (is_file($directory . '/' . $fileName));

        return $fileName;
    }

    private function normalizeFiles(mixed $input): array
    {
        if (!is_array($input) || !isset($input['name'])) {
            return [];
        }

        if (!is_array($input['name'])) {
            return [$input];
        }

        $files = [];
        foreach ($input['name'] as $index => $name) {
            $files[] = [
                'name' => $name,
                'type' => $input['type'][$index] ?? null,
                'tmp_name' => $input['tmp_name'][$index] ?? null,
                'error' => $input['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $input['size'][$index] ?? 0,
            ];
        }

        return $files;
    }

    private function movePhotoToStorage(string $source, string $destination): void
    {
        $moved = is_uploaded_file($source)
            ? move_uploaded_file($source, $destination)
            : copy($source, $destination);

        if (!$moved) {
            throw new RuntimeException('Failed to store uploaded photo.');
        }
    }

    private function detectMimeType(string $path, string $fallback): string
    {
        if (!is_file($path)) {
            return $fallback;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return $fallback;
        }

        $mimeType = finfo_file($finfo, $path) ?: $fallback;
        finfo_close($finfo);

        return $mimeType;
    }
}
