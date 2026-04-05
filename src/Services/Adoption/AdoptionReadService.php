<?php

declare(strict_types=1);

namespace App\Services\Adoption;

use App\Core\Database;
use App\Models\AdoptionApplication;
use App\Models\AdoptionCompletion;
use App\Models\AdoptionInterview;
use App\Models\AdoptionSeminar;
use App\Support\InputNormalizer;
use RuntimeException;

class AdoptionReadService
{
    public function __construct(
        private readonly AdoptionApplication $applications,
        private readonly AdoptionInterview $interviews,
        private readonly AdoptionSeminar $seminars,
        private readonly AdoptionCompletion $completions,
        private readonly AdoptionStatusPolicy $statusPolicy,
        private readonly AdoptionBillingSummary $billingSummary
    ) {
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        $result = $this->applications->paginate($filters, $page, $perPage);

        foreach ($result['items'] as &$item) {
            $item['billing_summary'] = $this->billingSummary->summarizeForApplication((int) $item['id']);
            $item['days_in_stage'] = InputNormalizer::daysSince($item['updated_at']);
        }
        unset($item);

        return $result;
    }

    public function get(int $id): array
    {
        $application = $this->applications->find($id);
        if ($application === false) {
            throw new RuntimeException('Adoption application not found.');
        }

        $application['interviews'] = $this->interviews->listByApplication($id);
        $application['seminars'] = $this->seminars->listByApplication($id);
        $application['completion'] = $this->completions->findByApplication($id) ?: null;
        $application['invoices'] = $this->billingSummary->linkedInvoices($id);
        $application['billing_summary'] = $this->billingSummary->summarize($application['invoices']);
        $application['available_statuses'] = $this->statusPolicy->availableStatuses((string) $application['status']);
        $application['days_in_stage'] = InputNormalizer::daysSince($application['updated_at']);

        return $application;
    }

    public function pipelineStats(): array
    {
        $metrics = Database::fetchAll(
            "SELECT metric_group, metric_key, metric_value
             FROM (
                 SELECT 'status' AS metric_group, status AS metric_key, COUNT(*) AS metric_value
                 FROM adoption_applications
                 WHERE is_deleted = 0
                 GROUP BY status

                 UNION ALL

                 SELECT 'summary' AS metric_group, 'upcoming_interviews' AS metric_key, COUNT(*) AS metric_value
                 FROM adoption_interviews
                 WHERE status = 'scheduled'
                   AND scheduled_date >= NOW()

                 UNION ALL

                 SELECT 'summary' AS metric_group, 'upcoming_seminars' AS metric_key, COUNT(*) AS metric_value
                 FROM adoption_seminars
                 WHERE status IN ('scheduled', 'in_progress')
                   AND scheduled_date >= NOW()
             ) AS pipeline_metrics"
        );

        $counts = [];
        $summary = [];

        foreach ($metrics as $row) {
            $group = (string) ($row['metric_group'] ?? '');
            $key = (string) ($row['metric_key'] ?? '');
            $value = (int) ($row['metric_value'] ?? 0);

            if ($key === '') {
                continue;
            }

            if ($group === 'status') {
                $counts[$key] = $value;
                continue;
            }

            if ($group === 'summary') {
                $summary[$key] = $value;
            }
        }

        return [
            'statuses' => $this->statusPolicy->buildPipelineStatuses($counts),
            'upcoming_interviews' => (int) ($summary['upcoming_interviews'] ?? 0),
            'upcoming_seminars' => (int) ($summary['upcoming_seminars'] ?? 0),
            'ready_for_completion' => (int) ($counts['seminar_completed'] ?? 0) + (int) ($counts['pending_payment'] ?? 0),
        ];
    }

    public function seminarsList(array $filters = []): array
    {
        $seminars = $this->seminars->list($filters);
        foreach ($seminars as &$seminar) {
            $seminar['attendees'] = $this->seminars->attendees((int) $seminar['id']);
        }
        unset($seminar);

        return $seminars;
    }

    public function staffOptions(): array
    {
        return Database::fetchAll(
            'SELECT u.id,
                    CONCAT(u.first_name, " ", u.last_name) AS full_name,
                    u.email,
                    r.display_name AS role_display_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.is_deleted = 0
               AND u.is_active = 1
             ORDER BY u.first_name ASC, u.last_name ASC'
        );
    }

    public function statusLabels(): array
    {
        return $this->statusPolicy->labels();
    }
}
