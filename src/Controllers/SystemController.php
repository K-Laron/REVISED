<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Services\BackupService;
use App\Services\SystemSettingsService;
use App\Support\Pagination;
use RuntimeException;

class SystemController
{
    use InteractsWithApi;

    private BackupService $backups;
    private SystemSettingsService $settings;

    public function __construct()
    {
        $this->backups = new BackupService();
        $this->settings = new SystemSettingsService();
    }

    public function ping(Request $request): Response
    {
        return Response::success([
            'timestamp' => date(DATE_ATOM),
            'status' => 'ok',
        ], 'API is reachable.');
    }

    public function validateTest(Request $request): Response
    {
        $validator = new Validator($request->body());
        $validator->rules([
            'email' => 'required|email|max:255',
            'name' => 'required|string|min:2|max:100',
        ]);

        if ($validator->fails()) {
            return Response::error(422, 'VALIDATION_ERROR', 'The given data was invalid.', $validator->errors());
        }

        return Response::success($request->body(), 'Validation passed.');
    }

    public function health(Request $request): Response
    {
        return Response::success($this->backups->health(), 'System health retrieved successfully.');
    }

    public function createBackup(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'backup_type' => 'nullable|in:full,schema_only',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $backup = $this->backups->createBackup(
                (string) $request->body('backup_type', 'full'),
                $this->currentUserId($request),
                $request
            );
        } catch (RuntimeException $exception) {
            return Response::error(500, 'BACKUP_FAILED', $exception->getMessage());
        }

        return Response::success($backup, 'Backup created successfully.');
    }

    public function listBackups(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 10, 50);
        $result = $this->backups->listBackups($page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Backups retrieved successfully.');
    }

    public function settings(Request $request): Response
    {
        return Response::success($this->settings->settings(), 'System settings retrieved successfully.');
    }

    public function updateSettings(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'app_name' => 'required|string|min:3|max:150',
            'organization_name' => 'required|string|min:3|max:150',
            'public_portal_enabled' => 'required|boolean',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|phone_ph',
            'office_address' => 'nullable|string|max:500',
            'mail_delivery_mode' => 'required|in:smtp,log_only,disabled',
            'maintenance_message' => 'required|string|min:10|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $settings = $this->settings->update($request->body(), $this->currentUserId($request), $request);

        return Response::success($settings, 'System settings updated successfully.');
    }

    public function updateMaintenance(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'enabled' => 'required|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $settings = $this->settings->setMaintenance(
            filter_var($request->body('enabled'), FILTER_VALIDATE_BOOL),
            $request->body('message'),
            $this->currentUserId($request),
            $request
        );

        return Response::success($settings, 'Maintenance mode updated successfully.');
    }

    public function readiness(Request $request): Response
    {
        return Response::success($this->settings->readiness(), 'Deployment readiness retrieved successfully.');
    }

    public function restoreBackup(Request $request, string $id): Response
    {
        $expectedConfirmation = 'RESTORE ' . $id;
        if ((string) $request->body('restore_confirmation', '') !== $expectedConfirmation) {
            return $this->validationError(
                ['restore_confirmation' => ['Type ' . $expectedConfirmation . ' to continue.']],
                'Backup restore requires an exact typed confirmation.'
            );
        }

        try {
            $backup = $this->backups->restoreBackup((int) $id, $this->currentUserId($request), $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'BACKUP_RESTORE_BLOCKED', $exception->getMessage());
        }

        return Response::success($backup, 'Backup restored successfully.');
    }
}
