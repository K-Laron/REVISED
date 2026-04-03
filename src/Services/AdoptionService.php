<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Request;
use App\Models\AdoptionApplication;
use App\Models\AdoptionCompletion;
use App\Models\AdoptionInterview;
use App\Models\AdoptionSeminar;
use App\Models\Animal;
use App\Models\User;
use App\Services\Adoption\AdoptionBillingSummary;
use App\Services\Adoption\AdoptionPortalService;
use App\Services\Adoption\AdoptionReadService;
use App\Services\Adoption\AdoptionStatusPolicy;
use App\Services\Adoption\AdoptionWorkflowService;

class AdoptionService
{
    private AdoptionReadService $reads;
    private AdoptionPortalService $portal;
    private AdoptionWorkflowService $workflow;

    public function __construct(
        ?AdoptionReadService $reads = null,
        ?AdoptionPortalService $portal = null,
        ?AdoptionWorkflowService $workflow = null
    )
    {
        if ($reads !== null && $portal !== null && $workflow !== null) {
            $this->reads = $reads;
            $this->portal = $portal;
            $this->workflow = $workflow;
            return;
        }

        $applications = new AdoptionApplication();
        $interviews = new AdoptionInterview();
        $seminars = new AdoptionSeminar();
        $completions = new AdoptionCompletion();
        $animals = new Animal();
        $users = new User();
        $pdfs = new PdfService();
        $audit = new AuditService();
        $notifications = new NotificationService();

        $statusPolicy = new AdoptionStatusPolicy();
        $billingSummary = new AdoptionBillingSummary();

        $this->reads = $reads ?? new AdoptionReadService(
            $applications,
            $interviews,
            $seminars,
            $completions,
            $statusPolicy,
            $billingSummary
        );

        $this->portal = $portal ?? new AdoptionPortalService(
            $applications,
            $animals,
            $users,
            $this->reads,
            $audit,
            $notifications
        );

        $this->workflow = $workflow ?? new AdoptionWorkflowService(
            $applications,
            $interviews,
            $seminars,
            $completions,
            $animals,
            $this->reads,
            $statusPolicy,
            $billingSummary,
            $pdfs,
            $audit,
            $notifications
        );
    }

    public function list(array $filters, int $page, int $perPage): array
    {
        return $this->reads->list($filters, $page, $perPage);
    }

    public function get(int $id): array
    {
        return $this->reads->get($id);
    }

    public function pipelineStats(): array
    {
        return $this->reads->pipelineStats();
    }

    public function seminarsList(array $filters = []): array
    {
        return $this->reads->seminarsList($filters);
    }

    public function staffOptions(): array
    {
        return $this->reads->staffOptions();
    }

    public function statusLabels(): array
    {
        return $this->reads->statusLabels();
    }

    public function featuredAnimals(int $limit = 6): array
    {
        return $this->portal->featuredAnimals($limit);
    }

    public function availableAnimals(array $filters, int $page, int $perPage): array
    {
        return $this->portal->availableAnimals($filters, $page, $perPage);
    }

    public function publicAnimalDetail(int|string $id): array
    {
        return $this->portal->publicAnimalDetail($id);
    }

    public function registerAdopter(array $data, Request $request): array
    {
        return $this->portal->registerAdopter($data, $request);
    }

    public function submitPortalApplication(int $userId, array $data, array $file, Request $request): array
    {
        return $this->portal->submitPortalApplication($userId, $data, $file, $request);
    }

    public function myApplications(int $userId): array
    {
        return $this->portal->myApplications($userId);
    }

    public function updateStatus(int $applicationId, string $status, int $userId, Request $request): array
    {
        return $this->workflow->updateStatus($applicationId, $status, $userId, $request);
    }

    public function reject(int $applicationId, string $reason, int $userId, Request $request): array
    {
        return $this->workflow->reject($applicationId, $reason, $userId, $request);
    }

    public function scheduleInterview(int $applicationId, array $data, int $userId, Request $request): array
    {
        return $this->workflow->scheduleInterview($applicationId, $data, $userId, $request);
    }

    public function updateInterview(int $interviewId, array $data, int $userId, Request $request): array
    {
        return $this->workflow->updateInterview($interviewId, $data, $userId, $request);
    }

    public function createSeminar(array $data, int $userId, Request $request): array
    {
        return $this->workflow->createSeminar($data, $userId, $request);
    }

    public function registerAttendee(int $seminarId, int $applicationId, int $userId, Request $request): array
    {
        return $this->workflow->registerAttendee($seminarId, $applicationId, $userId, $request);
    }

    public function updateAttendance(int $seminarId, int $applicationId, string $attendanceStatus, int $userId, Request $request): array
    {
        return $this->workflow->updateAttendance($seminarId, $applicationId, $attendanceStatus, $userId, $request);
    }

    public function complete(int $applicationId, array $data, int $userId, Request $request): array
    {
        return $this->workflow->complete($applicationId, $data, $userId, $request);
    }

    public function certificate(int $applicationId): array
    {
        return $this->workflow->certificate($applicationId);
    }
}
