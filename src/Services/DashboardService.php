<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DashboardStats;
use App\Models\MedicalRecord;
use App\Support\Cache\FileCacheStore;

class DashboardService
{
    private const CACHE_TTL_SECONDS = 15;

    public function __construct(
        private readonly FileCacheStore $cache,
        private readonly DashboardStats $dashboardStats,
        private readonly AuditLog $auditLog,
        private readonly InventoryService $inventory,
        private readonly MedicalRecord $medicalRecords,
        private readonly AdoptionService $adoptions,
        private readonly BillingService $billing
    ) {
    }

    public function bootstrap(): array
    {
        return $this->buildBootstrapPayload();
    }

    public function stats(): array
    {
        return $this->formatStats($this->getDashboardMetrics());
    }

    public function intakeChart(): array
    {
        return $this->trendCharts()['intake'];
    }

    public function adoptionChart(): array
    {
        return $this->trendCharts()['adoptions'];
    }

    public function occupancyChart(): array
    {
        return $this->formatOccupancyChart($this->getDashboardMetrics()['occupancy'] ?? []);
    }

    public function medicalChart(): array
    {
        return $this->formatMedicalChart($this->getDashboardMetrics()['medical'] ?? []);
    }

    public function actionQueue(array $user): array
    {
        $key = 'dashboard.action_queue.v1.' . $this->accessProfileCacheKey($user);

        return $this->remember($key, fn (): array => $this->buildActionQueue($user));
    }

    private function buildActionQueue(array $user): array
    {
        $items = [];

        if ($this->canAccess($user, 'inventory.read')) {
            $alerts = $this->inventory->alertCounts();
            $lowStockCount = (int) ($alerts['low_stock_count'] ?? 0);
            $expiringCount = (int) ($alerts['expiring_count'] ?? 0);

            if ($lowStockCount > 0) {
                $items[] = $this->queueItem(
                    'inventory-low-stock',
                    'Inventory',
                    'Low stock needs review',
                    $lowStockCount,
                    'High',
                    $lowStockCount . ' inventory item' . ($lowStockCount === 1 ? ' is' : 's are') . ' at or below reorder level.',
                    '/inventory'
                );
            }

            if ($expiringCount > 0) {
                $items[] = $this->queueItem(
                    'inventory-expiring',
                    'Inventory',
                    'Expiring inventory is approaching',
                    $expiringCount,
                    'Medium',
                    $expiringCount . ' stocked item' . ($expiringCount === 1 ? ' is' : 's are') . ' due to expire within 30 days.',
                    '/inventory'
                );
            }
        }

        if ($this->canAccess($user, 'medical.read')) {
            $dueSummary = $this->medicalRecords->dueSummary();
            $dueVaccinations = (int) ($dueSummary['due_vaccinations'] ?? 0);
            $dueDewormings = (int) ($dueSummary['due_dewormings'] ?? 0);
            $medicalDueCount = $dueVaccinations + $dueDewormings;

            if ($medicalDueCount > 0) {
                $items[] = $this->queueItem(
                    'medical-due',
                    'Medical',
                    'Upcoming care follow-ups',
                    $medicalDueCount,
                    'High',
                    $dueVaccinations . ' vaccination' . ($dueVaccinations === 1 ? '' : 's') . ' and '
                        . $dueDewormings . ' deworming follow-up' . ($dueDewormings === 1 ? '' : 's')
                        . ' are due soon.',
                    '/medical'
                );
            }
        }

        if ($this->canAccess($user, 'adoptions.read')) {
            $pipeline = $this->adoptions->pipelineStats();
            $readyForCompletion = (int) ($pipeline['ready_for_completion'] ?? 0);
            $upcomingReviews = (int) ($pipeline['upcoming_interviews'] ?? 0) + (int) ($pipeline['upcoming_seminars'] ?? 0);

            if ($readyForCompletion > 0) {
                $items[] = $this->queueItem(
                    'adoptions-ready',
                    'Adoptions',
                    'Applications are ready to close out',
                    $readyForCompletion,
                    'High',
                    $readyForCompletion . ' adoption application' . ($readyForCompletion === 1 ? ' is' : 's are') . ' at seminar-complete or payment-pending stages.',
                    '/adoptions'
                );
            }

            if ($upcomingReviews > 0) {
                $items[] = $this->queueItem(
                    'adoptions-upcoming',
                    'Adoptions',
                    'Interviews and seminars are coming up',
                    $upcomingReviews,
                    'Medium',
                    $upcomingReviews . ' scheduled adoption review touchpoint' . ($upcomingReviews === 1 ? ' is' : 's are') . ' upcoming.',
                    '/adoptions'
                );
            }
        }

        if ($this->canAccess($user, 'billing.read')) {
            $billingStats = $this->billing->stats();
            $overdueCount = (int) ($billingStats['overdue_count'] ?? 0);

            if ($overdueCount > 0) {
                $items[] = $this->queueItem(
                    'billing-overdue',
                    'Billing',
                    'Overdue balances need follow-up',
                    $overdueCount,
                    'High',
                    $overdueCount . ' invoice' . ($overdueCount === 1 ? ' is' : 's are') . ' already overdue.',
                    '/billing'
                );
            }
        }

        usort($items, fn (array $left, array $right): int => $this->compareQueueItems($left, $right));

        return $items;
    }

    private function buildBootstrapPayload(): array
    {
        $metrics = $this->getDashboardMetrics();

        return [
            'stats' => $this->formatStats($metrics),
            'charts' => [
                'intake' => $this->intakeChart(),
                'adoptions' => $this->adoptionChart(),
                'occupancy' => $this->formatOccupancyChart($metrics['occupancy'] ?? []),
                'medical' => $this->formatMedicalChart($metrics['medical'] ?? []),
            ],
            'activity' => $this->recentActivity(10),
        ];
    }

    public function recentActivity(int $limit = 18): array
    {
        return $limit === 10
            ? $this->remember('dashboard.activity.v1', fn (): array => $this->auditLog->recent($limit))
            : $this->auditLog->recent($limit);
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

    private function canAccess(array $user, string $permission): bool
    {
        if (($user['role_name'] ?? '') === 'super_admin') {
            return true;
        }

        return in_array($permission, $user['permissions'] ?? [], true);
    }

    private function queueItem(
        string $key,
        string $module,
        string $label,
        int $count,
        string $urgency,
        string $summary,
        string $href
    ): array {
        return [
            'key' => $key,
            'module' => $module,
            'label' => $label,
            'count' => $count,
            'urgency' => $urgency,
            'summary' => $summary,
            'href' => $href,
        ];
    }

    private function compareQueueItems(array $left, array $right): int
    {
        $priority = ['High' => 0, 'Medium' => 1, 'Low' => 2];
        $leftPriority = $priority[$left['urgency']] ?? 10;
        $rightPriority = $priority[$right['urgency']] ?? 10;

        if ($leftPriority !== $rightPriority) {
            return $leftPriority <=> $rightPriority;
        }

        if ($left['count'] !== $right['count']) {
            return $right['count'] <=> $left['count'];
        }

        return strcmp((string) $left['label'], (string) $right['label']);
    }

    private function accessProfileCacheKey(array $user): string
    {
        $permissions = $user['permissions'] ?? [];
        $permissions = is_array($permissions) ? array_values(array_unique(array_map('strval', $permissions))) : [];
        sort($permissions);

        return sha1(json_encode([
            'role' => (string) ($user['role_name'] ?? ''),
            'permissions' => $permissions,
        ], JSON_THROW_ON_ERROR));
    }

    private function remember(string $key, callable $resolver): array
    {
        $value = $this->cache->remember($key, self::CACHE_TTL_SECONDS, $resolver);

        return is_array($value) ? $value : [];
    }

    private function trendCharts(): array
    {
        $charts = $this->remember('dashboard.charts.trends.v1', fn (): array => $this->buildTrendCharts());

        return [
            'intake' => is_array($charts['intake'] ?? null) ? $charts['intake'] : $this->fillMonthlySeries([], 'total'),
            'adoptions' => is_array($charts['adoptions'] ?? null) ? $charts['adoptions'] : $this->fillMonthlySeries([], 'total'),
        ];
    }

    private function buildTrendCharts(): array
    {
        $rows = $this->dashboardStats->getTrends();

        $intakeRows = [];
        $adoptionRows = [];

        foreach ($rows as $row) {
            $source = (string) ($row['source'] ?? '');

            if ($source === 'intake') {
                $intakeRows[] = $row;
                continue;
            }

            if ($source === 'adoptions') {
                $adoptionRows[] = $row;
            }
        }

        return [
            'intake' => $this->fillMonthlySeries($intakeRows, 'total'),
            'adoptions' => $this->fillMonthlySeries($adoptionRows, 'total'),
        ];
    }

    private function getDashboardMetrics(): array
    {
        $metrics = $this->remember('dashboard.metrics.v1', fn (): array => $this->buildDashboardMetrics());

        return [
            'stats' => is_array($metrics['stats'] ?? null) ? $metrics['stats'] : [],
            'occupancy' => is_array($metrics['occupancy'] ?? null) ? $metrics['occupancy'] : [],
            'medical' => is_array($metrics['medical'] ?? null) ? $metrics['medical'] : [],
        ];
    }

    private function buildDashboardMetrics(): array
    {
        $rows = $this->dashboardStats->getMetrics();

        $stats = [];
        $occupancy = [];
        $medical = [];

        foreach ($rows as $row) {
            $group = (string) ($row['metric_group'] ?? '');
            $key = trim((string) ($row['metric_key'] ?? ''));
            $value = (int) ($row['metric_value'] ?? 0);

            if ($key === '') {
                continue;
            }

            if ($group === 'stats') {
                $stats[$key] = $value;
                continue;
            }

            if ($group === 'occupancy') {
                $occupancy[] = [
                    'status' => $key,
                    'total' => $value,
                ];
                continue;
            }

            if ($group === 'medical') {
                $medical[] = [
                    'procedure_type' => $key,
                    'total' => $value,
                ];
            }
        }

        usort(
            $occupancy,
            static fn (array $left, array $right): int => strcmp(
                (string) ($left['status'] ?? ''),
                (string) ($right['status'] ?? '')
            )
        );

        usort(
            $medical,
            static fn (array $left, array $right): int => strcmp(
                (string) ($left['procedure_type'] ?? ''),
                (string) ($right['procedure_type'] ?? '')
            )
        );

        return [
            'stats' => $stats,
            'occupancy' => $occupancy,
            'medical' => $medical,
        ];
    }

    private function formatStats(array $metrics): array
    {
        $stats = is_array($metrics['stats'] ?? null) ? $metrics['stats'] : [];
        $occupancyRows = is_array($metrics['occupancy'] ?? null) ? $metrics['occupancy'] : [];

        $animals = (int) ($stats['animals_total'] ?? 0);
        $medicalTotal = (int) ($stats['animals_under_care'] ?? 0);
        $adoptionsTotal = (int) ($stats['adoption_pipeline'] ?? 0);
        $occupied = 0;
        $totalKennels = 0;

        foreach ($occupancyRows as $row) {
            $status = (string) ($row['status'] ?? '');
            $count = (int) ($row['total'] ?? 0);
            $totalKennels += $count;

            if ($status === 'Occupied') {
                $occupied = $count;
            }
        }

        $totalKennels = max(1, $totalKennels);

        return [
            [
                'label' => 'Total Animals',
                'value' => $animals,
                'meta' => 'In shelter records',
            ],
            [
                'label' => 'Under Care',
                'value' => $medicalTotal,
                'meta' => 'Medical status',
            ],
            [
                'label' => 'Adoption Pipeline',
                'value' => $adoptionsTotal,
                'meta' => 'Open applications',
            ],
            [
                'label' => 'Kennel Occupancy',
                'value' => round(($occupied / $totalKennels) * 100) . '%',
                'meta' => $occupied . ' of ' . $totalKennels . ' occupied',
            ],
        ];
    }

    private function formatOccupancyChart(array $rows): array
    {
        return [
            'labels' => array_map(
                static fn (array $row): string => (string) ($row['status'] ?? ''),
                $rows
            ),
            'datasets' => [[
                'label' => 'Kennels',
                'data' => array_map(
                    static fn (array $row): int => (int) ($row['total'] ?? 0),
                    $rows
                ),
            ]],
        ];
    }

    private function formatMedicalChart(array $rows): array
    {
        return [
            'labels' => array_column($rows, 'procedure_type'),
            'datasets' => [[
                'label' => 'Procedures',
                'data' => array_map('intval', array_column($rows, 'total')),
            ]],
        ];
    }
}
