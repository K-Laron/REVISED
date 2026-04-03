<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\AuditLog;
use App\Models\ReportTemplate;
use App\Services\Reports\AnimalDossierService;
use App\Services\Reports\ReportRange;
use RuntimeException;

class ReportService
{
    private AuditLog $auditLogs;
    private ReportTemplate $templates;
    private AnimalDossierService $dossiers;

    public function __construct(
        ?AuditLog $auditLogs = null,
        ?ReportTemplate $templates = null,
        ?AnimalDossierService $dossiers = null
    )
    {
        $this->auditLogs = $auditLogs ?? new AuditLog();
        $this->templates = $templates ?? new ReportTemplate();
        $this->dossiers = $dossiers ?? new AnimalDossierService(new AnimalService());
    }

    public function generate(string $reportType, array $filters): array
    {
        $filters = $this->normalizeFilters($filters);

        return match ($reportType) {
            'intake' => $this->intakeReport($filters),
            'medical' => $this->medicalReport($filters),
            'adoptions' => $this->adoptionsReport($filters),
            'billing' => $this->billingReport($filters),
            'inventory' => $this->inventoryReport($filters),
            'census' => $this->censusReport($filters),
            default => throw new RuntimeException('Unsupported report type.'),
        };
    }

    public function templates(int $userId): array
    {
        return $this->templates->listForUser($userId);
    }

    public function saveTemplate(string $name, string $reportType, array $configuration, int $userId): array
    {
        $templateId = $this->templates->create($name, $reportType, $configuration, $userId);

        return $this->templates->findAccessible($templateId, $userId) ?: [];
    }

    public function auditTrail(array $filters, int $page, int $perPage): array
    {
        return $this->auditLogs->paginate($filters, $page, $perPage);
    }

    public function animalDossier(int $animalId): array
    {
        return $this->dossiers->assemble($animalId);
    }

    private function normalizeFilters(array $filters): array
    {
        return ReportRange::fromFilters($filters)->toArray();
    }

    private function intakeReport(array $filters): array
    {
        $rows = $this->groupedRows(
            'animals',
            'intake_date',
            $filters,
            'COUNT(*) AS total_animals',
            ['SUM(species = "Dog") AS dogs', 'SUM(species = "Cat") AS cats'],
            'is_deleted = 0'
        );
        $summary = Database::fetch(
            'SELECT COUNT(*) AS total_animals,
                    SUM(species = "Dog") AS dogs,
                    SUM(species = "Cat") AS cats,
                    SUM(intake_type = "Stray") AS stray_count,
                    SUM(intake_type = "Surrendered") AS surrendered_count
             FROM animals
             WHERE intake_date BETWEEN :start AND :end
               AND is_deleted = 0',
            $this->rangeBindings($filters)
        ) ?: [];

        return $this->formatReport('intake', 'Animal Intake Report', $filters, $summary, $rows, ['period', 'total_animals', 'dogs', 'cats']);
    }

    private function medicalReport(array $filters): array
    {
        $rows = $this->groupedRows(
            'medical_records',
            'record_date',
            $filters,
            'COUNT(*) AS total_records',
            [
                'SUM(procedure_type = "vaccination") AS vaccinations',
                'SUM(procedure_type = "deworming") AS dewormings',
                'SUM(procedure_type = "treatment") AS treatments',
                'SUM(procedure_type = "surgery") AS surgeries',
            ],
            'is_deleted = 0'
        );
        $summary = Database::fetch(
            'SELECT COUNT(*) AS total_records,
                    SUM(procedure_type = "vaccination") AS vaccinations,
                    SUM(procedure_type = "deworming") AS dewormings,
                    SUM(procedure_type = "treatment") AS treatments,
                    SUM(procedure_type = "surgery") AS surgeries
             FROM medical_records
             WHERE record_date BETWEEN :start AND :end
               AND is_deleted = 0',
            $this->rangeBindings($filters)
        ) ?: [];

        return $this->formatReport('medical', 'Medical Activity Report', $filters, $summary, $rows, ['period', 'total_records', 'vaccinations', 'dewormings', 'treatments', 'surgeries']);
    }

    private function adoptionsReport(array $filters): array
    {
        $rows = $this->groupedRows(
            'adoption_applications',
            'created_at',
            $filters,
            'COUNT(*) AS applications',
            [
                'SUM(status = "completed") AS completed',
                'SUM(status = "rejected") AS rejected',
                'SUM(status = "pending_review") AS pending_review',
            ],
            'is_deleted = 0'
        );
        $summary = Database::fetch(
            'SELECT COUNT(*) AS applications,
                    SUM(status = "completed") AS completed,
                    SUM(status = "rejected") AS rejected,
                    SUM(status = "pending_review") AS pending_review
             FROM adoption_applications
             WHERE created_at BETWEEN :start AND :end
               AND is_deleted = 0',
            $this->rangeBindings($filters)
        ) ?: [];

        return $this->formatReport('adoptions', 'Adoption Pipeline Report', $filters, $summary, $rows, ['period', 'applications', 'completed', 'rejected', 'pending_review']);
    }

    private function billingReport(array $filters): array
    {
        $rows = $this->groupedRows(
            'payments',
            'payment_date',
            $filters,
            'COUNT(*) AS payments_count',
            ['COALESCE(SUM(amount), 0) AS amount_collected']
        );
        $summary = Database::fetch(
            'SELECT COUNT(*) AS payments_count,
                    COALESCE(SUM(amount), 0) AS amount_collected
             FROM payments
             WHERE payment_date BETWEEN :start AND :end',
            $this->rangeBindings($filters)
        ) ?: [];
        $invoiceSummary = Database::fetch(
            'SELECT COUNT(*) AS invoices_count,
                    COALESCE(SUM(total_amount), 0) AS billed_total,
                    COALESCE(SUM(balance_due), 0) AS outstanding_balance
             FROM invoices
             WHERE issue_date BETWEEN :start_date AND :end_date
               AND is_deleted = 0',
            [
                'start_date' => $filters['start_date'],
                'end_date' => $filters['end_date'],
            ]
        ) ?: [];

        return $this->formatReport('billing', 'Billing Collections Report', $filters, $summary + $invoiceSummary, $rows, ['period', 'payments_count', 'amount_collected']);
    }

    private function inventoryReport(array $filters): array
    {
        $rows = $this->groupedRows(
            'stock_transactions',
            'transacted_at',
            $filters,
            'COUNT(*) AS transactions_count',
            [
                'COALESCE(SUM(quantity), 0) AS total_units',
                'SUM(transaction_type = "stock_in") AS stock_in_count',
                'SUM(transaction_type = "stock_out") AS stock_out_count',
                'SUM(transaction_type = "adjustment") AS adjustment_count',
            ]
        );
        $summary = Database::fetch(
            'SELECT COUNT(*) AS transactions_count,
                    COALESCE(SUM(quantity), 0) AS total_units,
                    SUM(transaction_type = "stock_in") AS stock_in_count,
                    SUM(transaction_type = "stock_out") AS stock_out_count,
                    SUM(transaction_type = "adjustment") AS adjustment_count
             FROM stock_transactions
             WHERE transacted_at BETWEEN :start AND :end',
            $this->rangeBindings($filters)
        ) ?: [];

        return $this->formatReport(
            'inventory',
            'Inventory Movement Report',
            $filters,
            $summary,
            $rows,
            ['period', 'transactions_count', 'total_units', 'stock_in_count', 'stock_out_count', 'adjustment_count']
        );
    }

    private function censusReport(array $filters): array
    {
        $rows = Database::fetchAll(
            'SELECT status,
                    species,
                    COUNT(*) AS total_animals
             FROM animals
             WHERE is_deleted = 0
             GROUP BY status, species
             ORDER BY status ASC, species ASC'
        );
        $summary = Database::fetch(
            'SELECT COUNT(*) AS total_animals,
                    SUM(status = "Available") AS available,
                    SUM(status = "Adopted") AS adopted,
                    SUM(status = "Under Treatment") AS under_treatment,
                    SUM(status = "Quarantine") AS quarantine
             FROM animals
             WHERE is_deleted = 0'
        ) ?: [];
        $occupancy = Database::fetch(
            'SELECT COUNT(*) AS occupied_kennels
             FROM kennel_assignments
             WHERE released_at IS NULL'
        ) ?: [];

        return [
            'type' => 'census',
            'title' => 'Animal Census Report',
            'filters' => $filters,
            'summary' => $summary + $occupancy,
            'rows' => $rows,
            'columns' => ['status', 'species', 'total_animals'],
            'chart' => [
                'labels' => array_map(static fn (array $row) => $row['status'] . ' / ' . $row['species'], $rows),
                'values' => array_map(static fn (array $row) => (int) $row['total_animals'], $rows),
            ],
            'generated_at' => date(DATE_ATOM),
        ];
    }

    private function groupedRows(
        string $table,
        string $dateColumn,
        array $filters,
        string $aggregateSql,
        array $extraAggregates = [],
        string $extraWhere = '1 = 1'
    ): array {
        $periodSql = $this->periodSql($dateColumn, $filters['group_by']);
        $selects = implode(",\n                    ", array_merge([$aggregateSql], $extraAggregates));

        return Database::fetchAll(
            'SELECT ' . $periodSql . ' AS period,
                    ' . $selects . '
             FROM ' . $table . '
             WHERE ' . $extraWhere . '
               AND ' . $dateColumn . ' BETWEEN :start AND :end
             GROUP BY period
             ORDER BY MIN(' . $dateColumn . ') ASC',
            $this->rangeBindings($filters)
        );
    }

    private function periodSql(string $column, string $groupBy): string
    {
        return match ($groupBy) {
            'day' => 'DATE_FORMAT(' . $column . ', "%Y-%m-%d")',
            'week' => 'DATE_FORMAT(DATE_SUB(' . $column . ', INTERVAL WEEKDAY(' . $column . ') DAY), "%Y-%m-%d")',
            'month' => 'DATE_FORMAT(' . $column . ', "%Y-%m")',
            'quarter' => 'CONCAT(YEAR(' . $column . '), "-Q", QUARTER(' . $column . '))',
            'year' => 'DATE_FORMAT(' . $column . ', "%Y")',
            default => 'DATE_FORMAT(' . $column . ', "%Y-%m")',
        };
    }

    private function rangeBindings(array $filters): array
    {
        return [
            'start' => $filters['start_date'] . ' 00:00:00',
            'end' => $filters['end_date'] . ' 23:59:59',
        ];
    }

    private function formatReport(string $type, string $title, array $filters, array $summary, array $rows, array $columns): array
    {
        $valueColumn = $columns[1] ?? null;

        return [
            'type' => $type,
            'title' => $title,
            'filters' => $filters,
            'summary' => $summary,
            'rows' => $rows,
            'columns' => $columns,
            'chart' => [
                'labels' => array_map(static fn (array $row) => (string) $row['period'], $rows),
                'values' => $valueColumn === null ? [] : array_map(static fn (array $row) => (float) ($row[$valueColumn] ?? 0), $rows),
            ],
            'generated_at' => date(DATE_ATOM),
        ];
    }
}
