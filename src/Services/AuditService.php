<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Request;
use App\Models\AuditLog;
use Throwable;

class AuditService
{
    public function __construct(
        private readonly AuditLog $auditLogs,
        private readonly Logger $logger
    ) {
    }

    public function record(
        ?int $userId,
        string $action,
        string $module,
        ?string $recordTable,
        int|string|null $recordId,
        array $oldValues,
        array $newValues,
        ?Request $request = null
    ): void {
        try {
            $this->auditLogs->record(
                $userId,
                $action,
                $module,
                $recordTable,
                $recordId,
                $oldValues,
                $newValues,
                $request?->ip(),
                $request?->userAgent()
            );
        } catch (Throwable $exception) {
            $this->logger->warning('Audit log insert failed.', ['error' => $exception->getMessage()]);
        }
    }
}
