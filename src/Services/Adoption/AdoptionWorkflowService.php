<?php

declare(strict_types=1);

namespace App\Services\Adoption;

use App\Core\Database;
use App\Core\Request;
use App\Models\AdoptionApplication;
use App\Models\AdoptionCompletion;
use App\Models\AdoptionInterview;
use App\Models\AdoptionSeminar;
use App\Models\Animal;
use App\Services\AuditService;
use App\Services\NotificationService;
use App\Services\PdfService;
use App\Support\InputNormalizer;
use RuntimeException;

class AdoptionWorkflowService
{
    public function __construct(
        private readonly AdoptionApplication $applications,
        private readonly AdoptionInterview $interviews,
        private readonly AdoptionSeminar $seminars,
        private readonly AdoptionCompletion $completions,
        private readonly Animal $animals,
        private readonly AdoptionReadService $reads,
        private readonly AdoptionStatusPolicy $statusPolicy,
        private readonly AdoptionBillingSummary $billingSummary,
        private readonly PdfService $pdfs,
        private readonly AuditService $audit,
        private readonly NotificationService $notifications
    ) {
    }

    public function updateStatus(int $applicationId, string $status, int $userId, Request $request): array
    {
        $current = $this->reads->get($applicationId);
        $this->statusPolicy->assertTransition((string) $current['status'], $status);

        if ($status === 'completed') {
            if ((string) $current['status'] === 'completed') {
                return $current;
            }

            return $this->complete($applicationId, [
                'completion_date' => date('Y-m-d H:i:s'),
            ], $userId, $request);
        }

        $this->applications->updateStatus($applicationId, $status, null, null, $userId);
        $updated = $this->reads->get($applicationId);
        $this->audit->record($userId, 'update', 'adoptions', 'adoption_applications', $applicationId, $current, $updated, $request);

        return $updated;
    }

    public function reject(int $applicationId, string $reason, int $userId, Request $request): array
    {
        $current = $this->reads->get($applicationId);
        $this->statusPolicy->assertTransition((string) $current['status'], 'rejected');
        $this->applications->updateStatus($applicationId, 'rejected', $reason, null, $userId);
        $updated = $this->reads->get($applicationId);
        $this->audit->record($userId, 'update', 'adoptions', 'adoption_applications', $applicationId, $current, $updated, $request);

        return $updated;
    }

    public function scheduleInterview(int $applicationId, array $data, int $userId, Request $request): array
    {
        $current = $this->reads->get($applicationId);
        if (!in_array((string) $current['status'], ['pending_review', 'interview_scheduled'], true)) {
            throw new RuntimeException('This application cannot be scheduled for interview at its current stage.');
        }

        $scheduledDate = (string) InputNormalizer::dateTime($data['scheduled_date']);
        if (strtotime($scheduledDate) <= time()) {
            throw new RuntimeException('Interview schedule must be in the future.');
        }

        $payload = [
            'application_id' => $applicationId,
            'scheduled_date' => $scheduledDate,
            'interview_type' => (string) $data['interview_type'],
            'video_call_link' => InputNormalizer::nullIfBlank($data['video_call_link'] ?? null),
            'location' => InputNormalizer::nullIfBlank($data['location'] ?? null),
            'status' => 'scheduled',
            'screening_checklist' => null,
            'home_assessment_notes' => null,
            'pet_care_knowledge_score' => null,
            'overall_recommendation' => null,
            'interviewer_notes' => null,
            'conducted_by' => ($data['conducted_by'] ?? '') !== '' ? (int) $data['conducted_by'] : null,
            'completed_at' => null,
        ];

        Database::beginTransaction();
        try {
            $interviewId = $this->interviews->create($payload);
            $this->applications->updateStatus($applicationId, 'interview_scheduled', null, null, $userId);
            $this->notifyUser(
                (int) $current['adopter_id'],
                'interview_scheduled',
                'Interview scheduled',
                'Your adoption interview for ' . ($current['animal_name'] ?: 'your selected animal') . ' has been scheduled on ' . date('F j, Y g:i A', strtotime($scheduledDate)) . '.',
                '/adopt/apply'
            );
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->reads->get($applicationId);
        $this->audit->record($userId, 'create', 'adoptions', 'adoption_interviews', $interviewId, [], $payload, $request);

        return $updated;
    }

    public function updateInterview(int $interviewId, array $data, int $userId, Request $request): array
    {
        $current = $this->interviews->find($interviewId);
        if ($current === false) {
            throw new RuntimeException('Adoption interview not found.');
        }

        $application = $this->reads->get((int) $current['application_id']);
        $payload = [
            'scheduled_date' => (string) InputNormalizer::dateTime($data['scheduled_date']),
            'interview_type' => (string) $data['interview_type'],
            'video_call_link' => InputNormalizer::nullIfBlank($data['video_call_link'] ?? null),
            'location' => InputNormalizer::nullIfBlank($data['location'] ?? null),
            'status' => (string) $data['status'],
            'screening_checklist' => $this->screeningChecklistJson($data['screening_checklist'] ?? null),
            'home_assessment_notes' => InputNormalizer::nullIfBlank($data['home_assessment_notes'] ?? null),
            'pet_care_knowledge_score' => ($data['pet_care_knowledge_score'] ?? '') !== '' ? (int) $data['pet_care_knowledge_score'] : null,
            'overall_recommendation' => InputNormalizer::nullIfBlank($data['overall_recommendation'] ?? null),
            'interviewer_notes' => InputNormalizer::nullIfBlank($data['interviewer_notes'] ?? null),
            'conducted_by' => ($data['conducted_by'] ?? '') !== '' ? (int) $data['conducted_by'] : null,
            'completed_at' => (string) $data['status'] === 'completed' ? date('Y-m-d H:i:s') : null,
        ];

        Database::beginTransaction();
        try {
            $this->interviews->update($interviewId, $payload);

            if ((string) $data['status'] === 'completed') {
                $this->setApplicationStatusFromSystem((int) $current['application_id'], 'interview_completed', $userId);
            } elseif ((string) $application['status'] === 'pending_review') {
                $this->setApplicationStatusFromSystem((int) $current['application_id'], 'interview_scheduled', $userId);
            }

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->reads->get((int) $current['application_id']);
        $this->audit->record($userId, 'update', 'adoptions', 'adoption_interviews', $interviewId, $current, $payload, $request);

        return $updated;
    }

    public function createSeminar(array $data, int $userId, Request $request): array
    {
        $scheduledDate = (string) InputNormalizer::dateTime($data['scheduled_date']);
        $endTime = ($data['end_time'] ?? '') !== '' ? InputNormalizer::dateTime($data['end_time']) : null;

        if (strtotime($scheduledDate) <= time()) {
            throw new RuntimeException('Seminar schedule must be in the future.');
        }

        if ($endTime !== null && strtotime($endTime) <= strtotime($scheduledDate)) {
            throw new RuntimeException('Seminar end time must be after the start time.');
        }

        $payload = [
            'title' => trim((string) $data['title']),
            'scheduled_date' => $scheduledDate,
            'end_time' => $endTime,
            'location' => trim((string) $data['location']),
            'capacity' => max(1, (int) $data['capacity']),
            'facilitator_id' => ($data['facilitator_id'] ?? '') !== '' ? (int) $data['facilitator_id'] : null,
            'description' => InputNormalizer::nullIfBlank($data['description'] ?? null),
            'status' => (string) ($data['status'] ?? 'scheduled'),
            'created_by' => $userId,
        ];

        $seminarId = $this->seminars->create($payload);
        $seminar = $this->seminars->find($seminarId);
        $this->audit->record($userId, 'create', 'adoptions', 'adoption_seminars', $seminarId, [], $seminar ?: $payload, $request);

        return $this->reads->seminarsList();
    }

    public function registerAttendee(int $seminarId, int $applicationId, int $userId, Request $request): array
    {
        $seminar = $this->seminars->find($seminarId);
        if ($seminar === false) {
            throw new RuntimeException('Adoption seminar not found.');
        }

        $application = $this->reads->get($applicationId);
        if (in_array((string) $application['status'], ['completed', 'rejected', 'withdrawn'], true)) {
            throw new RuntimeException('This application is no longer eligible for seminar registration.');
        }

        if ($this->seminars->attendee($seminarId, $applicationId) !== false) {
            throw new RuntimeException('The application is already registered for this seminar.');
        }

        if ((int) $seminar['attendee_count'] >= (int) $seminar['capacity']) {
            throw new RuntimeException('This seminar is already at full capacity.');
        }

        Database::beginTransaction();
        try {
            $attendeeId = $this->seminars->addAttendee($seminarId, $applicationId);
            $this->setApplicationStatusFromSystem($applicationId, 'seminar_scheduled', $userId);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->reads->get($applicationId);
        $this->audit->record($userId, 'create', 'adoptions', 'seminar_attendees', $attendeeId, [], [
            'seminar_id' => $seminarId,
            'application_id' => $applicationId,
        ], $request);

        return $updated;
    }

    public function updateAttendance(int $seminarId, int $applicationId, string $attendanceStatus, int $userId, Request $request): array
    {
        $current = $this->seminars->attendee($seminarId, $applicationId);
        if ($current === false) {
            throw new RuntimeException('Seminar attendee registration not found.');
        }

        Database::beginTransaction();
        try {
            $this->seminars->updateAttendance($seminarId, $applicationId, $attendanceStatus, $userId);

            if ($attendanceStatus === 'attended') {
                $targetStatus = $this->billingSummary->summarizeForApplication($applicationId)['payment_state'] === 'pending'
                    ? 'pending_payment'
                    : 'seminar_completed';
                $this->setApplicationStatusFromSystem($applicationId, $targetStatus, $userId);
            }

            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->reads->get($applicationId);
        $this->audit->record($userId, 'update', 'adoptions', 'seminar_attendees', (int) $current['id'], $current, [
            'attendance_status' => $attendanceStatus,
        ], $request);

        return $updated;
    }

    public function complete(int $applicationId, array $data, int $userId, Request $request): array
    {
        $application = $this->reads->get($applicationId);
        if ($application['completion'] !== null) {
            throw new RuntimeException('This adoption application has already been completed.');
        }

        if (($application['animal_id'] ?? null) === null) {
            throw new RuntimeException('An animal must be assigned before the adoption can be completed.');
        }

        if (!in_array((string) $application['status'], ['seminar_completed', 'pending_payment'], true)) {
            throw new RuntimeException('This application is not yet ready for completion.');
        }

        $billing = $this->billingSummary->summarize($application['invoices']);
        $paymentConfirmed = InputNormalizer::bool($data['payment_confirmed'] ?? false);
        if ($billing['payment_state'] === 'pending' && !$paymentConfirmed) {
            throw new RuntimeException('Outstanding adoption billing must be settled or manually confirmed before completion.');
        }

        $completionPayload = [
            'application_id' => $applicationId,
            'animal_id' => (int) $application['animal_id'],
            'adopter_id' => (int) $application['adopter_id'],
            'completion_date' => (string) InputNormalizer::dateTime($data['completion_date'] ?? date('Y-m-d H:i:s')),
            'payment_confirmed' => $paymentConfirmed ? 1 : 0,
            'contract_signed' => InputNormalizer::bool($data['contract_signed'] ?? false) ? 1 : 0,
            'contract_signature_path' => null,
            'medical_records_provided' => InputNormalizer::bool($data['medical_records_provided'] ?? false) ? 1 : 0,
            'spay_neuter_agreement' => InputNormalizer::bool($data['spay_neuter_agreement'] ?? false) ? 1 : 0,
            'certificate_path' => null,
            'notes' => InputNormalizer::nullIfBlank($data['notes'] ?? null),
            'processed_by' => $userId,
        ];

        Database::beginTransaction();
        try {
            $completionId = $this->completions->create($completionPayload);
            $completion = $this->completions->findByApplication($applicationId);
            if ($completion === false) {
                throw new RuntimeException('Adoption completion record was not created.');
            }

            $certificatePath = $this->pdfs->adoptionCertificate($application, $completion);
            $this->completions->updateCertificatePath($completionId, $certificatePath);

            $this->applications->updateStatus($applicationId, 'completed', null, null, $userId);
            $this->animals->updateStatus(
                (int) $application['animal_id'],
                'Adopted',
                'Adoption application completed.',
                $completionPayload['completion_date'],
                $userId
            );
            $this->animals->releaseKennelOccupancy((int) $application['animal_id'], $userId);
            Database::commit();
        } catch (\Throwable $exception) {
            Database::rollBack();
            throw $exception;
        }

        $updated = $this->reads->get($applicationId);
        $this->audit->record($userId, 'create', 'adoptions', 'adoption_completions', $completionId, [], $updated['completion'] ?? [], $request);

        return $updated;
    }

    public function certificate(int $applicationId): array
    {
        $application = $this->reads->get($applicationId);
        $completion = $application['completion'];

        if (!is_array($completion) || ($completion['certificate_path'] ?? null) === null) {
            throw new RuntimeException('Adoption certificate not found.');
        }

        return $completion;
    }

    private function setApplicationStatusFromSystem(int $applicationId, string $targetStatus, int $userId): void
    {
        $current = $this->applications->find($applicationId);
        if ($current === false || (string) $current['status'] === $targetStatus) {
            return;
        }

        if (!$this->statusPolicy->canTransition((string) $current['status'], $targetStatus)) {
            return;
        }

        $this->applications->updateStatus($applicationId, $targetStatus, null, null, $userId);
    }

    private function screeningChecklistJson(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        json_decode($value, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Screening checklist must be valid JSON.');
        }

        return $value;
    }

    private function notifyUser(int $userId, string $type, string $title, string $message, ?string $link): void
    {
        $this->notifications->create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
        ]);
    }
}
