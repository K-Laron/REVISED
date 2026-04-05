<?php

declare(strict_types=1);

namespace Tests\Support\Cache;

use App\Support\Cache\FileCacheStore;
use PHPUnit\Framework\TestCase;

final class FileCacheStoreTest extends TestCase
{
    private string $cacheDirectory;
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dashboard-cache-' . bin2hex(random_bytes(5));
        $this->cachePath = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'app_cache.json';
        mkdir($this->cacheDirectory, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_file($this->cachePath)) {
            @unlink($this->cachePath);
        }

        if (is_dir($this->cacheDirectory)) {
            @rmdir($this->cacheDirectory);
        }

        parent::tearDown();
    }

    public function testRememberReturnsCachedPayloadUntilTheTtlExpires(): void
    {
        $store = new FileCacheStore($this->cachePath);
        $calls = 0;

        $first = $store->remember('dashboard.bootstrap', 30, static function () use (&$calls): array {
            $calls++;

            return ['count' => 1];
        });
        $second = $store->remember('dashboard.bootstrap', 30, static function () use (&$calls): array {
            $calls++;

            return ['count' => 2];
        });

        self::assertSame(['count' => 1], $first);
        self::assertSame(['count' => 1], $second);
        self::assertSame(1, $calls);
    }

    public function testRememberSupportsNestedResolutionWithoutDeadlocking(): void
    {
        $scriptPath = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'nested-remember.php';
        $rootPath = str_replace('\\', '/', dirname(__DIR__, 3));
        file_put_contents(
            $scriptPath,
            "<?php\n"
            . "require " . var_export($rootPath . '/bootstrap/autoload.php', true) . ";\n\n"
            . "\$store = new \\App\\Support\\Cache\\FileCacheStore(__DIR__ . '/nested-cache.json');\n"
            . "\$result = \$store->remember('dashboard.bootstrap', 30, function () use (\$store): array {\n"
            . "    return \$store->remember('dashboard.metrics', 30, static fn (): array => ['nested' => true]);\n"
            . "});\n\n"
            . "echo json_encode(\$result);\n"
        );

        $result = $this->runPhpScript($scriptPath, 2);

        self::assertFalse($result['timed_out'], 'Nested remember timed out, indicating a cache lock deadlock.');
        self::assertSame(0, $result['exit_code']);
        self::assertSame('{"nested":true}', trim($result['stdout']));
    }

    /**
     * @return array{timed_out: bool, exit_code: ?int, stdout: string, stderr: string}
     */
    private function runPhpScript(string $scriptPath, int $timeoutSeconds): array
    {
        $stdoutPath = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'nested-remember.stdout.log';
        $stderrPath = $this->cacheDirectory . DIRECTORY_SEPARATOR . 'nested-remember.stderr.log';

        $command = 'powershell -NoLogo -NoProfile -Command '
            . escapeshellarg(
                '$process = Start-Process -FilePath ' . $this->powershellLiteral(PHP_BINARY)
                . ' -ArgumentList ' . $this->powershellLiteral($scriptPath)
                . ' -WorkingDirectory ' . $this->powershellLiteral(dirname($scriptPath))
                . ' -RedirectStandardOutput ' . $this->powershellLiteral($stdoutPath)
                . ' -RedirectStandardError ' . $this->powershellLiteral($stderrPath)
                . ' -PassThru; '
                . 'if ($process.WaitForExit(' . ($timeoutSeconds * 1000) . ')) { exit $process.ExitCode } '
                . 'else { $process.Kill(); exit 124 }'
            );

        $output = [];
        exec($command, $output, $exitCode);

        $stdout = is_file($stdoutPath) ? (string) file_get_contents($stdoutPath) : '';
        $stderr = is_file($stderrPath) ? (string) file_get_contents($stderrPath) : '';
        $timedOut = $exitCode === 124;

        if (is_file($stdoutPath)) {
            @unlink($stdoutPath);
        }

        if (is_file($stderrPath)) {
            @unlink($stderrPath);
        }

        return [
            'timed_out' => $timedOut,
            'exit_code' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    private function powershellLiteral(string $value): string
    {
        return "'" . str_replace("'", "''", $value) . "'";
    }
}
