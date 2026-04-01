<?php

declare(strict_types=1);

namespace Tests\Services\Backup;

use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MySqlBackupRestorerTest extends TestCase
{
    /** @var string[] */
    private array $trackedPaths = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->trackedPaths) as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        parent::tearDown();
    }

    public function testRestoreRejectsChecksumMismatchBeforeRunningAnyImportCommand(): void
    {
        $class = 'App\\Services\\Backup\\MySqlBackupRestorer';
        if (!class_exists($class)) {
            self::fail('Expected App\\Services\\Backup\\MySqlBackupRestorer to exist.');
        }

        $commands = [];
        $compressedPath = $this->createCompressedBackup("CREATE TABLE demo (id INT);\n");

        $restorer = new $class(
            processRunner: static function (array $command, ?string $stdinFile = null) use (&$commands): void {
                $commands[] = ['command' => $command, 'stdin' => $stdinFile];
            },
            mysqlBinaryResolver: static fn (): string => 'mysql',
            scratchDatabaseFactory: static fn (string $database): string => $database . '_scratch'
        );

        try {
            $restorer->restore(
                ['checksum_sha256' => str_repeat('a', 64)],
                $compressedPath,
                $this->databaseConfig()
            );
            self::fail('Expected restore() to reject a mismatched checksum.');
        } catch (RuntimeException $exception) {
            self::assertSame('Backup checksum verification failed.', $exception->getMessage());
        }

        self::assertSame([], $commands);
    }

    public function testRestoreValidatesAgainstScratchDatabaseBeforeImportingLiveDatabase(): void
    {
        $class = 'App\\Services\\Backup\\MySqlBackupRestorer';
        if (!class_exists($class)) {
            self::fail('Expected App\\Services\\Backup\\MySqlBackupRestorer to exist.');
        }

        $commands = [];
        $stdinFiles = [];
        $compressedPath = $this->createCompressedBackup("CREATE TABLE demo (id INT);\n");
        $checksum = hash_file('sha256', $compressedPath);
        self::assertIsString($checksum);

        $restorer = new $class(
            processRunner: static function (array $command, ?string $stdinFile = null) use (&$commands, &$stdinFiles): void {
                $commands[] = $command;
                $stdinFiles[] = $stdinFile;
                if ($stdinFile !== null) {
                    self::assertFileExists($stdinFile);
                }
            },
            mysqlBinaryResolver: static fn (): string => 'mysql',
            scratchDatabaseFactory: static fn (string $database): string => $database . '_scratch'
        );

        $restorer->restore(
            ['checksum_sha256' => $checksum],
            $compressedPath,
            $this->databaseConfig()
        );

        self::assertSame(
            [
                ['mysql', '--host=127.0.0.1', '--port=3306', '--user=root', '--password=secret', '--default-character-set=utf8mb4', '--execute=DROP DATABASE IF EXISTS `catarman_shelter_scratch`'],
                ['mysql', '--host=127.0.0.1', '--port=3306', '--user=root', '--password=secret', '--default-character-set=utf8mb4', '--execute=CREATE DATABASE `catarman_shelter_scratch` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'],
                ['mysql', '--host=127.0.0.1', '--port=3306', '--user=root', '--password=secret', '--default-character-set=utf8mb4', 'catarman_shelter_scratch'],
                ['mysql', '--host=127.0.0.1', '--port=3306', '--user=root', '--password=secret', '--default-character-set=utf8mb4', '--execute=DROP DATABASE IF EXISTS `catarman_shelter_scratch`'],
                ['mysql', '--host=127.0.0.1', '--port=3306', '--user=root', '--password=secret', '--default-character-set=utf8mb4', 'catarman_shelter'],
            ],
            $commands
        );

        self::assertCount(5, $stdinFiles);
        self::assertSame([null, null], array_slice($stdinFiles, 0, 2));
        self::assertIsString($stdinFiles[2]);
        self::assertIsString($stdinFiles[4]);
        self::assertSame($stdinFiles[2], $stdinFiles[4]);
        self::assertFileDoesNotExist((string) $stdinFiles[2]);
    }

    private function createCompressedBackup(string $sql): string
    {
        $path = tempnam(sys_get_temp_dir(), 'backup-');
        self::assertNotFalse($path);

        $compressedPath = $path . '.sql.gz';
        $written = file_put_contents($compressedPath, gzencode($sql, 9));
        self::assertNotFalse($written);

        $this->trackedPaths[] = $compressedPath;

        return $compressedPath;
    }

    private function databaseConfig(): array
    {
        return [
            'host' => '127.0.0.1',
            'port' => 3306,
            'database' => 'catarman_shelter',
            'username' => 'root',
            'password' => 'secret',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];
    }
}
