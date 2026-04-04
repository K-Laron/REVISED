<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\MedicalRecord;
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

    public function actionQueue(array $user): array
    {
        $items = [];

        if ($this->canAccess($user, 'inventory.read')) {
            $alerts = (new InventoryService())->alerts();
            $lowStockCount = count($alerts['low_stock'] ?? []);
            $expiringCount = count($alerts['expiring'] ?? []);

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
            $medicalRecords = new MedicalRecord();
            $dueVaccinations = count($medicalRecords->dueVaccinations());
            $dueDewormings = count($medicalRecords->dueDewormings());
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
            $pipeline = (new AdoptionService())->pipelineStats();
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
            $billing = (new BillingService())->stats();
            $overdueCount = (int) ($billing['overdue_count'] ?? 0);

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

    public function recentActivity(int $limit = 18): array
    {
        return $limit === 10
            ? $this->bootstrap()['activity']
            : $this->buildRecentActivity($limit);
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
}
