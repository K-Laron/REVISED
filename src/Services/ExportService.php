<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class ExportService
{
    public function __construct(
        private readonly PdfService $pdfs
    ) {
    }

    public function reportCsv(array $report): string
    {
        $directory = dirname(__DIR__, 2) . '/storage/exports/reports';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create report export directory.');
        }

        $filename = $report['type'] . '-' . date('Ymd-His') . '.csv';
        $absolutePath = $directory . '/' . $filename;
        $handle = fopen($absolutePath, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Failed to create report CSV file.');
        }

        fputcsv($handle, $report['columns']);
        foreach ($report['rows'] as $row) {
            $record = [];
            foreach ($report['columns'] as $column) {
                $record[] = $row[$column] ?? '';
            }
            fputcsv($handle, $record);
        }

        fclose($handle);

        return 'storage/exports/reports/' . $filename;
    }

    public function reportPdf(array $report): string
    {
        return $this->pdfs->report($report);
    }

    public function animalDossierPdf(array $dossier): string
    {
        return $this->pdfs->animalDossier($dossier);
    }
}
