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
        $counts = $this->applications->buildPipelineStats();

        return [
            'statuses' => $this->statusPolicy->buildPipelineStatuses($counts),
            'upcoming_interviews' => (int) (Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM adoption_interviews
                 WHERE status = 'scheduled'
                   AND scheduled_date >= NOW()"
            )['aggregate'] ?? 0),
            'upcoming_seminars' => (int) (Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM adoption_seminars
                 WHERE status IN ('scheduled', 'in_progress')
                   AND scheduled_date >= NOW()"
            )['aggregate'] ?? 0),
            'ready_for_completion' => (int) (Database::fetch(
                "SELECT COUNT(*) AS aggregate
                 FROM adoption_applications
                 WHERE is_deleted = 0
                   AND status IN ('seminar_completed', 'pending_payment')"
            )['aggregate'] ?? 0),
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
