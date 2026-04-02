<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Support\Cache\FileCacheStore;

class DashboardService
{
    private FileCacheStore $cache;

    public function __construct(?FileCacheStore $cache = null)
    {
        $this->cache = $cache ?? new FileCacheStore();
    }

    public function bootstrap(): array
    {
        return $this->cache->remember(
            'dashboard.bootstrap.v1',
            15,
            fn (): array => $this->buildBootstrapPayload()
        );
    }

    public function stats(): array
    {
        return $this->bootstrap()['stats'];
    }

    public function intakeChart(): array
    {
        return $this->bootstrap()['charts']['intake'];
    }

    public function adoptionChart(): array
    {
        return $this->bootstrap()['charts']['adoptions'];
    }

    public function occupancyChart(): array
    {
        return $this->bootstrap()['charts']['occupancy'];
    }

    public function medicalChart(): array
    {
        return $this->bootstrap()['charts']['medical'];
    }

    public function recentActivity(int $limit = 10): array
    {
        return $limit === 10
            ? $this->bootstrap()['activity']
            : $this->buildRecentActivity($limit);
    }

    private function buildBootstrapPayload(): array
    {
        return [
            'stats' => $this->buildStats(),
            'charts' => [
                'intake' => $this->buildIntakeChart(),
                'adoptions' => $this->buildAdoptionChart(),
                'occupancy' => $this->buildOccupancyChart(),
                'medical' => $this->buildMedicalChart(),
            ],
            'activity' => $this->buildRecentActivity(),
        ];
    }

    private function buildStats(): array
    {
        $animals = (int) ($this->scalar('SELECT COUNT(*) FROM animals WHERE is_deleted = 0') ?? 0);
        $medical = (int) ($this->scalar("SELECT COUNT(*) FROM animals WHERE is_deleted = 0 AND status = 'Under Medical Care'") ?? 0);
        $adoptions = (int) ($this->scalar("SELECT COUNT(*) FROM adoption_applications WHERE is_deleted = 0 AND status NOT IN ('completed', 'rejected', 'withdrawn')") ?? 0);
        $occupied = (int) ($this->scalar("SELECT COUNT(*) FROM kennels WHERE is_deleted = 0 AND status = 'Occupied'") ?? 0);
        $totalKennels = max(1, (int) ($this->scalar('SELECT COUNT(*) FROM kennels WHERE is_deleted = 0') ?? 1));

        return [
            [
                'label' => 'Total Animals',
                'value' => $animals,
                'meta' => 'In shelter records',
            ],
            [
                'label' => 'Under Care',
                'value' => $medical,
                'meta' => 'Medical status',
            ],
            [
                'label' => 'Adoption Pipeline',
                'value' => $adoptions,
                'meta' => 'Open applications',
            ],
            [
                'label' => 'Kennel Occupancy',
                'value' => round(($occupied / $totalKennels) * 100) . '%',
                'meta' => $occupied . ' of ' . $totalKennels . ' occupied',
            ],
        ];
    }

    private function buildIntakeChart(): array
    {
        $rows = Database::fetchAll(
            "SELECT DATE_FORMAT(intake_date, '%Y-%m') AS month_key, COUNT(*) AS total
             FROM animals
             WHERE intake_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
               AND is_deleted = 0
             GROUP BY month_key
             ORDER BY month_key"
        );

        return $this->fillMonthlySeries($rows, 'total');
    }

    private function buildAdoptionChart(): array
    {
        $rows = Database::fetchAll(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, COUNT(*) AS total
             FROM adoption_applications
             WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
               AND is_deleted = 0
             GROUP BY month_key
             ORDER BY month_key"
        );

        return $this->fillMonthlySeries($rows, 'total');
    }

    private function buildOccupancyChart(): array
    {
        $rows = Database::fetchAll(
            "SELECT status, COUNT(*) AS total
             FROM kennels
             WHERE is_deleted = 0
             GROUP BY status
             ORDER BY status"
        );

        return [
            'labels' => array_column($rows, 'status'),
            'datasets' => [[
                'label' => 'Kennels',
                'data' => array_map('intval', array_column($rows, 'total')),
            ]],
        ];
    }

    private function buildMedicalChart(): array
    {
        $rows = Database::fetchAll(
            "SELECT procedure_type, COUNT(*) AS total
             FROM medical_records
             WHERE is_deleted = 0
             GROUP BY procedure_type
             ORDER BY procedure_type"
        );

        return [
            'labels' => array_column($rows, 'procedure_type'),
            'datasets' => [[
                'label' => 'Procedures',
                'data' => array_map('intval', array_column($rows, 'total')),
            ]],
        ];
    }

    private function buildRecentActivity(int $limit = 10): array
    {
        return Database::fetchAll(
            'SELECT action, module, record_id, created_at
             FROM audit_logs
             ORDER BY created_at DESC
             LIMIT ' . max(1, (int) $limit)
        );
    }

    private function scalar(string $sql): mixed
    {
        $row = Database::fetch($sql);

        if ($row === false) {
            return null;
        }

        return array_values($row)[0] ?? null;
    }

    private function fillMonthlySeries(array $rows, string $valueKey): array
    {
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['month_key']] = (int) $row[$valueKey];
        }

        $labels = [];
        $values = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $labels[] = date('M Y', strtotime($month . '-01'));
            $values[] = $indexed[$month] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Count',
                'data' => $values,
            ]],
        ];
    }
}
