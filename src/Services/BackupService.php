<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\ExceptionHandler;
use App\Core\Request;
use App\Models\SystemBackup;
use App\Services\Backup\MySqlBackupRestorer;
use App\Support\SystemSettings;
use PDO;
use RuntimeException;
use Throwable;

class BackupService
{
    private SystemBackup $backups;
    private AuditService $audit;

    public function __construct()
    {
        $this->backups = new SystemBackup();
        $this->audit = new AuditService();
    }

    public function health(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $uptimeSeconds = max(0, time() - ExceptionHandler::bootTimestamp());
        $database = [
            'status' => 'up',
            'database' => $config['database'],
        ];
        $status = 'ok';

        try {
            Database::fetch('SELECT 1 AS ok');
        } catch (Throwable $exception) {
            $database = [
                'status' => 'down',
                'database' => $config['database'],
                'error' => $exception->getMessage(),
            ];
            $status = 'degraded';
        }

        return [
            'status' => $status,
            'checked_at' => date(DATE_ATOM),
            'uptime_seconds' => $uptimeSeconds,
            'uptime_human' => $this->formatDuration($uptimeSeconds),
            'maintenance_mode' => ExceptionHandler::inMaintenanceMode(),
            'maintenance_source' => is_file(dirname(__DIR__, 2) . '/storage/maintenance.flag') ? 'legacy_flag' : 'settings',
            'maintenance_message' => (string) SystemSettings::get(
                'maintenance_message',
                'The system is currently under maintenance.'
            ),
            'database' => $database,
        ];
    }

    public function listBackups(int $page, int $perPage): array
    {
        return $this->backups->paginate($page, $perPage);
    }

    public function createBackup(string $backupType, ?int $userId, ?Request $request = null): array
    {
        if (!in_array($backupType, ['full', 'schema_only'], true)) {
            throw new RuntimeException('Unsupported backup type.');
        }

        $tables = $this->tableNames();
        $directory = dirname(__DIR__, 2) . '/storage/backups';
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create backup directory.');
        }

        $timestamp = date('Ymd-His');
        $fileName = sprintf('catarman-shelter-%s-%s.sql.gz', $backupType, $timestamp);
        $relativePath = 'storage/backups/' . $fileName;
        $absolutePath = dirname(__DIR__, 2) . '/' . $relativePath;

        $backupId = $this->backups->create([
            'backup_type' => $backupType,
            'file_path' => $relativePath,
            'file_size_bytes' => 0,
            'checksum_sha256' => null,
            'status' => 'started',
            'tables_included' => json_encode($tables, JSON_UNESCAPED_SLASHES),
            'error_message' => null,
            'started_at' => date('Y-m-d H:i:s'),
            'completed_at' => null,
            'created_by' => $userId,
            'restored_by' => null,
            'restored_at' => null,
        ]);

        try {
            $this->writeDump($absolutePath, $tables, $backupType === 'full');
            $fileSize = (int) (filesize($absolutePath) ?: 0);
            $checksum = hash_file('sha256', $absolutePath);
            if ($checksum === false) {
                throw new RuntimeException('Failed to compute backup checksum.');
            }

            $this->backups->markCompleted($backupId, $fileSize, $checksum);
            $backup = $this->backups->find($backupId);
            if ($backup === false) {
                throw new RuntimeException('Backup record was not found after completion.');
            }

            $this->audit->record($userId, 'create', 'system', 'system_backups', $backupId, [], $backup, $request);

            return $backup;
        } catch (Throwable $exception) {
            if (is_file($absolutePath)) {
                @unlink($absolutePath);
            }

            $this->backups->markFailed($backupId, $exception->getMessage());
            throw $exception;
        }
    }

    public function restoreBackup(int $backupId, int $userId, ?Request $request = null): array
    {
        $backup = $this->backups->find($backupId);
        if ($backup === false) {
            throw new RuntimeException('Backup not found.');
        }

        if (($backup['status'] ?? '') !== 'completed') {
            throw new RuntimeException('Only completed backups can be restored.');
        }

        $absolutePath = dirname(__DIR__, 2) . '/' . $backup['file_path'];
        if (!is_file($absolutePath)) {
            throw new RuntimeException('Backup file is missing.');
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        (new MySqlBackupRestorer())->restore($backup, $absolutePath, $config);

        try {
            $this->backups->markRestored($backupId, $userId);
        } catch (Throwable) {
            // The restored snapshot may not contain the current backup row. Do not fail the restore for that.
        }

        $updated = $this->backups->find($backupId) ?: $backup;
        $this->audit->record($userId, 'restore', 'system', 'system_backups', $backupId, [], ['restored' => true], $request);

        return $updated;
    }

    private function tableNames(): array
    {
        $rows = Database::fetchAll('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"');
        $tables = [];

        foreach ($rows as $row) {
            $values = array_values($row);
            if (isset($values[0]) && is_string($values[0])) {
                $tables[] = $values[0];
            }
        }

        sort($tables);

        return $tables;
    }

    private function writeDump(string $absolutePath, array $tables, bool $includeData): void
    {
        $stream = gzopen($absolutePath, 'wb9');
        if ($stream === false) {
            throw new RuntimeException('Failed to create compressed backup file.');
        }

        try {
            $this->writeLine($stream, '-- Catarman Animal Shelter database backup');
            $this->writeLine($stream, '-- Generated at ' . date(DATE_ATOM));
            $this->writeLine($stream, 'SET FOREIGN_KEY_CHECKS=0;');
            $this->writeLine($stream, '');

            foreach ($tables as $table) {
                $escapedTable = $this->escapeIdentifier($table);
                $createRow = Database::fetch('SHOW CREATE TABLE ' . $escapedTable);
                if ($createRow === false) {
                    throw new RuntimeException('Failed to inspect table [' . $table . '].');
                }

                $createSql = (string) ($createRow['Create Table'] ?? array_values($createRow)[1] ?? '');
                if ($createSql === '') {
                    throw new RuntimeException('Table definition was empty for [' . $table . '].');
                }

                $this->writeLine($stream, '-- Table: ' . $table);
                $this->writeLine($stream, 'DROP TABLE IF EXISTS ' . $escapedTable . ';');
                $this->writeLine($stream, $createSql . ';');

                if ($includeData) {
                    $this->writeTableData($stream, $table);
                }

                $this->writeLine($stream, '');
            }

            $this->writeLine($stream, 'SET FOREIGN_KEY_CHECKS=1;');
        } finally {
            gzclose($stream);
        }
    }

    private function writeTableData(mixed $stream, string $table): void
    {
        $statement = Database::query('SELECT * FROM ' . $this->escapeIdentifier($table));
        $batch = [];
        $columns = [];

        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            if ($columns === []) {
                $columns = array_keys($row);
            }

            $batch[] = $this->serializeRow($row);
            if (count($batch) >= 100) {
                $this->writeInsertBatch($stream, $table, $columns, $batch);
                $batch = [];
            }
        }

        if ($batch !== []) {
            $this->writeInsertBatch($stream, $table, $columns, $batch);
        }
    }

    private function writeInsertBatch(mixed $stream, string $table, array $columns, array $batch): void
    {
        if ($columns === [] || $batch === []) {
            return;
        }

        $columnSql = implode(', ', array_map(fn (string $column): string => $this->escapeIdentifier($column), $columns));
        $this->writeLine(
            $stream,
            'INSERT INTO ' . $this->escapeIdentifier($table) . ' (' . $columnSql . ') VALUES ' . implode(",\n", $batch) . ';'
        );
    }

    private function serializeRow(array $row): string
    {
        $connection = Database::connect();
        $values = array_map(static function (mixed $value) use ($connection): string {
            if ($value === null) {
                return 'NULL';
            }

            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            return $connection->quote((string) $value);
        }, array_values($row));

        return '(' . implode(', ', $values) . ')';
    }

    private function escapeIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function writeLine(mixed $stream, string $line): void
    {
        gzwrite($stream, $line . "\n");
    }

    private function formatDuration(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        $parts = [];
        if ($days > 0) {
            $parts[] = $days . 'd';
        }
        if ($hours > 0 || $parts !== []) {
            $parts[] = $hours . 'h';
        }
        $parts[] = $minutes . 'm';

        return implode(' ', $parts);
    }
}
