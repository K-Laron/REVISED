<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\AdoptionService;
use App\Support\InputNormalizer;
use App\Support\Pagination;
use RuntimeException;

class AdoptionController
{
    use InteractsWithApi;
    use RendersViews;

    private AdoptionService $adoptions;

    public function __construct()
    {
        $this->adoptions = new AdoptionService();
    }

    public function index(Request $request): Response
    {
        return $this->renderAppView('adoptions.index', [
            'title' => 'Adoptions',
            'extraCss' => ['/assets/css/adoptions.css'],
            'extraJs' => ['/assets/js/adoptions.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'statusLabels' => $this->adoptions->statusLabels(),
            'staff' => $this->adoptions->staffOptions(),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        try {
            $application = $this->adoptions->get((int) $id);
        } catch (RuntimeException) {
            return Response::redirect('/adoptions');
        }

        return $this->renderAppView('adoptions.show', [
            'title' => $application['application_number'],
            'extraCss' => ['/assets/css/adoptions.css'],
            'extraJs' => ['/assets/js/adoptions.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'application' => $application,
            'seminars' => $this->adoptions->seminarsList(['status' => 'scheduled']),
            'statusLabels' => $this->adoptions->statusLabels(),
            'staff' => $this->adoptions->staffOptions(),
        ]);
    }

    public function list(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 40);
        $result = $this->adoptions->list($request->query(), $page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Adoption applications retrieved successfully.');
    }

    public function get(Request $request, string $id): Response
    {
        try {
            $application = $this->adoptions->get((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($application, 'Adoption application retrieved successfully.');
    }

    public function updateStatus(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'status' => 'required|in:pending_review,interview_scheduled,interview_completed,seminar_scheduled,seminar_completed,pending_payment,completed,rejected,withdrawn',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $application = $this->adoptions->updateStatus((int) $id, (string) $request->body('status'), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'ADOPTION_STATUS_BLOCKED', $exception->getMessage());
        }

        return Response::success($application, 'Adoption status updated successfully.');
    }

    public function reject(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'rejection_reason' => 'required|string|min:5|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $application = $this->adoptions->reject((int) $id, (string) $request->body('rejection_reason'), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'ADOPTION_REJECT_BLOCKED', $exception->getMessage());
        }

        return Response::success($application, 'Adoption application rejected successfully.');
    }

    public function scheduleInterview(Request $request, string $id): Response
    {
        $payload = InputNormalizer::normalizeDateTimeFields($request->body(), ['scheduled_date']);
        $validator = (new Validator($payload))->rules([
            'scheduled_date' => 'required|date',
            'interview_type' => 'required|in:in_person,video_call',
            'video_call_link' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'conducted_by' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $application = $this->adoptions->scheduleInterview((int) $id, $payload, $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'INTERVIEW_CREATE_BLOCKED', $exception->getMessage());
        }

        return Response::success($application, 'Interview scheduled successfully.');
    }

    public function updateInterview(Request $request, string $id): Response
    {
        $payload = InputNormalizer::normalizeDateTimeFields($request->body(), ['scheduled_date']);
        $validator = (new Validator($payload))->rules([
            'scheduled_date' => 'required|date',
            'interview_type' => 'required|in:in_person,video_call',
            'video_call_link' => 'nullable|string|max:500',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:scheduled,completed,cancelled,no_show',
            'screening_checklist' => 'nullable|string|max:5000',
            'home_assessment_notes' => 'nullable|string|max:2000',
            'pet_care_knowledge_score' => 'nullable|integer|between:1,10',
            'overall_recommendation' => 'nullable|in:Approve,Conditional,Reject',
            'interviewer_notes' => 'nullable|string|max:2000',
            'conducted_by' => 'nullable|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $application = $this->adoptions->updateInterview((int) $id, $payload, $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'INTERVIEW_UPDATE_BLOCKED', $exception->getMessage());
        }

        return Response::success($application, 'Interview updated successfully.');
    }

    public function createSeminar(Request $request): Response
    {
        $payload = InputNormalizer::normalizeDateTimeFields($request->body(), ['scheduled_date', 'end_time']);
        $validator = (new Validator($payload))->rules([
            'title' => 'required|string|max:200',
            'scheduled_date' => 'required|date',
            'end_time' => 'nullable|date',
            'location' => 'required|string|max:255',
            'capacity' => 'required|integer|between:1,500',
            'facilitator_id' => 'nullable|integer|exists:users,id',
            'description' => 'nullable|string|max:2000',
            'status' => 'nullable|in:scheduled,in_progress,completed,cancelled',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $seminars = $this->adoptions->createSeminar($payload, $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'SEMINAR_CREATE_BLOCKED', $exception->getMessage());
        }

        return Response::success($seminars, 'Adoption seminar created successfully.');
    }

    public function listSeminars(Request $request): Response
    {
        return Response::success($this->adoptions->seminarsList($request->query()), 'Adoption seminars retrieved successfully.');
    }

    public function addAttendee(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'application_id' => 'required|integer|exists:adoption_applications,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $application = $this->adoptions->registerAttendee((int) $id, (int) $request->body('application_id'), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'SEMINAR_ATTENDEE_BLOCKED', $exception->getMessage());
        }

        return Response::success($application, 'Application registered for seminar successfully.');
    }

    public function updateAttendance(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'application_id' => 'required|integer|exists:adoption_applications,id',
            'attendance_status' => 'required|in:registered,attended,no_show',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $application = $this->adoptions->updateAttendance((int) $id, (int) $request->body('application_id'), (string) $request->body('attendance_status'), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'SEMINAR_ATTENDANCE_BLOCKED', $exception->getMessage());
        }

        return Response::success($application, 'Seminar attendance updated successfully.');
    }

    public function complete(Request $request, string $id): Response
    {
        $payload = InputNormalizer::normalizeDateTimeFields($request->body(), ['completion_date']);
        $validator = (new Validator($payload))->rules([
            'completion_date' => 'required|date',
            'payment_confirmed' => 'nullable|boolean',
            'contract_signed' => 'nullable|boolean',
            'medical_records_provided' => 'nullable|boolean',
            'spay_neuter_agreement' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $application = $this->adoptions->complete((int) $id, $payload, $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'ADOPTION_COMPLETE_BLOCKED', $exception->getMessage());
        }

        return Response::success($application, 'Adoption completed successfully.');
    }

    public function certificate(Request $request, string $id): Response
    {
        try {
            $completion = $this->adoptions->certificate((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        $path = dirname(__DIR__, 2) . '/' . $completion['certificate_path'];
        if (!is_file($path)) {
            return Response::error(404, 'NOT_FOUND', 'Adoption certificate file not found.');
        }

        return $this->fileDownloadResponse($path, 'application/pdf', 'adoption-certificate-' . (int) $id . '.pdf');
    }

    public function pipelineStats(Request $request): Response
    {
        return Response::success($this->adoptions->pipelineStats(), 'Adoption pipeline stats retrieved successfully.');
    }

}
