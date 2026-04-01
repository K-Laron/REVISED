<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Closure;
use RuntimeException;
use Throwable;

final class MySqlBackupRestorer
{
    private ?Closure $processRunner;
    private ?Closure $mysqlBinaryResolver;
    private ?Closure $scratchDatabaseFactory;

    public function __construct(
        ?callable $processRunner = null,
        ?callable $mysqlBinaryResolver = null,
        ?callable $scratchDatabaseFactory = null
    ) {
        $this->processRunner = $processRunner !== null ? Closure::fromCallable($processRunner) : null;
        $this->mysqlBinaryResolver = $mysqlBinaryResolver !== null ? Closure::fromCallable($mysqlBinaryResolver) : null;
        $this->scratchDatabaseFactory = $scratchDatabaseFactory !== null ? Closure::fromCallable($scratchDatabaseFactory) : null;
    }

    public function restore(array $backup, string $compressedBackupPath, array $databaseConfig): void
    {
        $this->assertChecksumMatches($backup, $compressedBackupPath);

        $databaseName = $this->assertDatabaseName((string) ($databaseConfig['database'] ?? ''));
        $scratchDatabase = $this->scratchDatabaseName($databaseName);
        if ($scratchDatabase === $databaseName) {
            throw new RuntimeException('Scratch database name must differ from the live database name.');
        }

        $mysqlBinary = $this->resolveMysqlBinary();
        $sqlPath = $this->decompressBackup($compressedBackupPath);
        $scratchCreated = false;

        try {
            $this->runMysql(
                $this->mysqlCommand($mysqlBinary, $databaseConfig, null, 'DROP DATABASE IF EXISTS ' . $this->quoteIdentifier($scratchDatabase))
            );
            $this->runMysql(
                $this->mysqlCommand(
                    $mysqlBinary,
                    $databaseConfig,
                    null,
                    sprintf(
                        'CREATE DATABASE %s CHARACTER SET %s COLLATE %s',
                        $this->quoteIdentifier($scratchDatabase),
                        $this->quoteCharset((string) ($databaseConfig['charset'] ?? 'utf8mb4')),
                        $this->quoteCollation((string) ($databaseConfig['collation'] ?? 'utf8mb4_unicode_ci'))
                    )
                )
            );
            $scratchCreated = true;

            $this->runMysql($this->mysqlCommand($mysqlBinary, $databaseConfig, $scratchDatabase), $sqlPath);
            $this->runMysql(
                $this->mysqlCommand($mysqlBinary, $databaseConfig, null, 'DROP DATABASE IF EXISTS ' . $this->quoteIdentifier($scratchDatabase))
            );
            $scratchCreated = false;

            $this->runMysql($this->mysqlCommand($mysqlBinary, $databaseConfig, $databaseName), $sqlPath);
        } catch (Throwable $exception) {
            if ($scratchCreated) {
                try {
                    $this->runMysql(
                        $this->mysqlCommand($mysqlBinary, $databaseConfig, null, 'DROP DATABASE IF EXISTS ' . $this->quoteIdentifier($scratchDatabase))
                    );
                } catch (Throwable) {
                    // Best effort cleanup only.
                }
            }

            throw $exception;
        } finally {
            if (is_file($sqlPath)) {
                @unlink($sqlPath);
            }
        }
    }

    private function assertChecksumMatches(array $backup, string $compressedBackupPath): void
    {
        $expectedChecksum = trim((string) ($backup['checksum_sha256'] ?? ''));
        if ($expectedChecksum === '') {
            throw new RuntimeException('Backup checksum metadata is missing.');
        }

        $actualChecksum = hash_file('sha256', $compressedBackupPath);
        if ($actualChecksum === false || !hash_equals($expectedChecksum, $actualChecksum)) {
            throw new RuntimeException('Backup checksum verification failed.');
        }
    }

    private function scratchDatabaseName(string $databaseName): string
    {
        if ($this->scratchDatabaseFactory !== null) {
            return $this->assertDatabaseName((string) ($this->scratchDatabaseFactory)($databaseName));
        }

        $suffix = bin2hex(random_bytes(4));

        return $this->assertDatabaseName(substr($databaseName . '_restore_' . $suffix, 0, 63));
    }

    private function resolveMysqlBinary(): string
    {
        if ($this->mysqlBinaryResolver !== null) {
            return (string) ($this->mysqlBinaryResolver)();
        }

        $configured = trim((string) ($_ENV['MYSQL_BIN'] ?? $_ENV['MYSQL_CLIENT_BINARY'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        $lookupCommand = PHP_OS_FAMILY === 'Windows'
            ? ['where.exe', 'mysql']
            : ['which', 'mysql'];

        [$exitCode, $stdout, $stderr] = $this->runProcess($lookupCommand);
        if ($exitCode !== 0) {
            throw new RuntimeException('MySQL client binary could not be resolved: ' . trim($stderr !== '' ? $stderr : $stdout));
        }

        $binary = trim(strtok($stdout, PHP_EOL) ?: '');
        if ($binary === '') {
            throw new RuntimeException('MySQL client binary could not be resolved.');
        }

        return $binary;
    }

    private function decompressBackup(string $compressedBackupPath): string
    {
        $stream = gzopen($compressedBackupPath, 'rb');
        if ($stream === false) {
            throw new RuntimeException('Failed to open the compressed backup file.');
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'restore-');
        if ($tmpPath === false) {
            gzclose($stream);
            throw new RuntimeException('Failed to create a temporary restore file.');
        }

        $sqlPath = $tmpPath . '.sql';
        @unlink($tmpPath);

        $handle = fopen($sqlPath, 'wb');
        if ($handle === false) {
            gzclose($stream);
            throw new RuntimeException('Failed to open the temporary restore file.');
        }

        try {
            while (!gzeof($stream)) {
                $chunk = gzread($stream, 65536);
                if ($chunk === false) {
                    throw new RuntimeException('Failed to read the compressed backup file.');
                }

                if ($chunk === '') {
                    continue;
                }

                if (fwrite($handle, $chunk) === false) {
                    throw new RuntimeException('Failed to write the temporary restore file.');
                }
            }
        } catch (Throwable $exception) {
            @unlink($sqlPath);
            throw $exception;
        } finally {
            fclose($handle);
            gzclose($stream);
        }

        return $sqlPath;
    }

    private function mysqlCommand(string $mysqlBinary, array $databaseConfig, ?string $databaseName = null, ?string $executeSql = null): array
    {
        $command = [
            $mysqlBinary,
            '--host=' . (string) ($databaseConfig['host'] ?? '127.0.0.1'),
            '--port=' . (string) ($databaseConfig['port'] ?? 3306),
            '--user=' . (string) ($databaseConfig['username'] ?? 'root'),
            '--password=' . (string) ($databaseConfig['password'] ?? ''),
            '--default-character-set=' . (string) ($databaseConfig['charset'] ?? 'utf8mb4'),
        ];

        if ($executeSql !== null) {
            $command[] = '--execute=' . $executeSql;
        } elseif ($databaseName !== null) {
            $command[] = $databaseName;
        }

        return $command;
    }

    private function runMysql(array $command, ?string $stdinFile = null): void
    {
        if ($this->processRunner !== null) {
            ($this->processRunner)($command, $stdinFile);
            return;
        }

        [$exitCode, $stdout, $stderr] = $this->runProcess($command, $stdinFile);
        if ($exitCode !== 0) {
            $message = trim($stderr !== '' ? $stderr : $stdout);
            throw new RuntimeException($message !== '' ? $message : 'MySQL restore command failed.');
        }
    }

    private function runProcess(array $command, ?string $stdinFile = null): array
    {
        $descriptorSpec = [
            0 => $stdinFile !== null ? ['file', $stdinFile, 'r'] : ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            throw new RuntimeException('Failed to launch the MySQL client process.');
        }

        if ($stdinFile === null && isset($pipes[0])) {
            fclose($pipes[0]);
        }

        $stdout = isset($pipes[1]) ? stream_get_contents($pipes[1]) : '';
        $stderr = isset($pipes[2]) ? stream_get_contents($pipes[2]) : '';

        if (isset($pipes[1]) && is_resource($pipes[1])) {
            fclose($pipes[1]);
        }

        if (isset($pipes[2]) && is_resource($pipes[2])) {
            fclose($pipes[2]);
        }

        $exitCode = proc_close($process);

        return [$exitCode, (string) $stdout, (string) $stderr];
    }

    private function assertDatabaseName(string $name): string
    {
        if ($name === '' || !preg_match('/^[A-Za-z0-9_]+$/', $name)) {
            throw new RuntimeException('Database name contains unsupported characters for restore operations.');
        }

        return $name;
    }

    private function quoteIdentifier(string $identifier): string
    {
        return '`' . str_replace('`', '``', $identifier) . '`';
    }

    private function quoteCharset(string $charset): string
    {
        if ($charset === '' || !preg_match('/^[A-Za-z0-9_]+$/', $charset)) {
            throw new RuntimeException('Database charset contains unsupported characters for restore operations.');
        }

        return $charset;
    }

    private function quoteCollation(string $collation): string
    {
        if ($collation === '' || !preg_match('/^[A-Za-z0-9_]+$/', $collation)) {
            throw new RuntimeException('Database collation contains unsupported characters for restore operations.');
        }

        return $collation;
    }
}
