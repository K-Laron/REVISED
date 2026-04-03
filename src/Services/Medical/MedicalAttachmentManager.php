<?php

declare(strict_types=1);

namespace App\Services\Medical;

use App\Support\MediaPath;
use RuntimeException;

class MedicalAttachmentManager
{
    public function syncLabResults(int $medicalRecordId, array $labResults, mixed $labAttachmentInput = null, array $existingLabResults = []): array
    {
        $attachmentSync = [
            'new_files' => [],
            'obsolete_files' => [],
        ];

        $rows = $this->attachUploadedLabImages($medicalRecordId, $labResults, $labAttachmentInput, $attachmentSync);
        $attachmentSync['obsolete_files'] = $this->diffAttachmentPaths(
            $this->extractAttachmentPaths($existingLabResults),
            $this->extractAttachmentPaths($rows)
        );

        return [
            'rows' => $rows,
            'new_files' => $attachmentSync['new_files'],
            'obsolete_files' => $attachmentSync['obsolete_files'],
        ];
    }

    public function normalizeLabResults(array $rows): array
    {
        foreach ($rows as &$row) {
            $row['attachment_path'] = MediaPath::normalizePublicImagePath($row['attachment_path'] ?? null);
        }
        unset($row);

        return $rows;
    }

    public function deleteStoredFiles(array $paths): void
    {
        foreach (array_values(array_unique($paths)) as $path) {
            $normalizedPath = $this->normalizeRelativeMediaPath($path);
            if ($normalizedPath === null) {
                continue;
            }

            $absolutePath = dirname(__DIR__, 3) . '/public/' . str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $normalizedPath);
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }
        }
    }

    private function attachUploadedLabImages(int $medicalRecordId, array $labResults, mixed $labAttachmentInput, array &$attachmentSync): array
    {
        $files = $this->normalizeFiles($labAttachmentInput);
        if ($files === []) {
            return $labResults;
        }

        foreach ($labResults as $index => &$labResult) {
            if (trim((string) ($labResult['test_name'] ?? '')) === '') {
                unset($labResult['attachment_index']);
                continue;
            }

            $attachmentIndex = isset($labResult['attachment_index']) ? (int) $labResult['attachment_index'] : null;
            unset($labResult['attachment_index']);

            if ($attachmentIndex === null || !isset($files[$attachmentIndex])) {
                continue;
            }

            $storedPath = $this->storeLabAttachment($medicalRecordId, $files[$attachmentIndex]);
            if ($storedPath !== null) {
                $labResult['attachment_path'] = $storedPath;
                $attachmentSync['new_files'][] = $storedPath;
            }
        }
        unset($labResult);

        return $labResults;
    }

    private function storeLabAttachment(int $medicalRecordId, array $file): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $source = (string) ($file['tmp_name'] ?? '');
        if (!is_uploaded_file($source) && !is_file($source)) {
            return null;
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $directory = dirname(__DIR__, 3) . '/public/uploads/medical/lab-results/' . $medicalRecordId;

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to prepare medical attachment storage.');
        }

        do {
            $fileName = 'lab-result-' . bin2hex(random_bytes(12)) . '.' . $extension;
            $absolutePath = $directory . '/' . $fileName;
        } while (is_file($absolutePath));

        $moved = is_uploaded_file($source)
            ? move_uploaded_file($source, $absolutePath)
            : copy($source, $absolutePath);

        if (!$moved) {
            throw new RuntimeException('Failed to store the uploaded medical attachment.');
        }

        return 'uploads/medical/lab-results/' . $medicalRecordId . '/' . $fileName;
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
            $files[(int) $index] = [
                'name' => $name,
                'type' => $input['type'][$index] ?? null,
                'tmp_name' => $input['tmp_name'][$index] ?? null,
                'error' => $input['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $input['size'][$index] ?? 0,
            ];
        }

        return $files;
    }

    private function extractAttachmentPaths(array $rows): array
    {
        $paths = [];

        foreach ($rows as $row) {
            $path = $this->normalizeRelativeMediaPath($row['attachment_path'] ?? null);
            if ($path === null) {
                continue;
            }

            $paths[] = $path;
        }

        return array_values(array_unique($paths));
    }

    private function diffAttachmentPaths(array $existingPaths, array $nextPaths): array
    {
        $nextLookup = array_fill_keys($nextPaths, true);

        return array_values(array_filter($existingPaths, static fn (string $path): bool => !isset($nextLookup[$path])));
    }

    private function normalizeRelativeMediaPath(?string $path): ?string
    {
        $normalizedPath = ltrim(str_replace('\\', '/', trim((string) $path)), '/');

        if ($normalizedPath === '' || str_contains($normalizedPath, '..')) {
            return null;
        }

        return $normalizedPath;
    }
}
