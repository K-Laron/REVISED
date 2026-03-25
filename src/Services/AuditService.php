<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Logger;
use App\Core\Request;
use Throwable;

class AuditService
{
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
            Database::execute(
                'INSERT INTO audit_logs (user_id, action, module, record_table, record_id, old_values, new_values, ip_address, user_agent, request_id)
                 VALUES (:user_id, :action, :module, :record_table, :record_id, :old_values, :new_values, :ip_address, :user_agent, :request_id)',
                [
                    'user_id' => $userId,
                    'action' => $action,
                    'module' => $module,
                    'record_table' => $recordTable,
                    'record_id' => $recordId !== null ? (int) $recordId : null,
                    'old_values' => $oldValues === [] ? null : json_encode($oldValues, JSON_UNESCAPED_SLASHES),
                    'new_values' => $newValues === [] ? null : json_encode($newValues, JSON_UNESCAPED_SLASHES),
                    'ip_address' => $request?->ip(),
                    'user_agent' => $request ? mb_substr($request->userAgent(), 0, 500) : null,
                    'request_id' => bin2hex(random_bytes(16)),
                ]
            );
        } catch (Throwable $exception) {
            Logger::warning('Audit log insert failed.', ['error' => $exception->getMessage()]);
        }
    }
}
