<?php declare(strict_types=1);

namespace Bref\Test\Sam;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class PhpRuntimeTest extends TestCase
{
    use ArraySubsetAsserts;

    public function test invocation without event()
    {
        [$result, $logs] = $this->invokeLambda();

        self::assertEquals('Hello world', $result, $logs);
    }

    public function test invocation with event()
    {
        [$result, $logs] = $this->invokeLambda([
            'name' => 'Abby',
        ]);

        self::assertEquals('Hello Abby', $result, $logs);
    }

    public function test stdout ends up in logs()
    {
        [$result, $logs] = $this->invokeLambda([
            'stdout' => true,
        ]);

        self::assertStringNotContainsString('This is a test log by writing to stdout', $result);
        self::assertStringContainsString('This is a test log by writing to stdout', $logs);
    }

    public function test stderr ends up in logs()
    {
        [$result, $logs] = $this->invokeLambda([
            'stderr' => true,
        ]);

        self::assertStringNotContainsString('This is a test log by writing to stderr', $result);
        self::assertStringContainsString('This is a test log by writing to stderr', $logs);
    }

    public function test error_log function()
    {
        [$result, $logs] = $this->invokeLambda([
            'error_log' => true,
        ]);

        self::assertStringNotContainsString('This is a test log from error_log', $result);
        self::assertStringContainsString('This is a test log from error_log', $logs);
    }

    public function test uncaught exception appears in logs and is reported as an invocation error()
    {
        [$result, $logs] = $this->invokeLambda([
            'exception' => true,
        ]);

        $this->assertInvocationError(
            $result,
            $logs,
            'Exception',
            'This is an uncaught exception',
            '#0 /var/task/src/Runtime/LambdaRuntime.php('
        );
    }

    public function test error appears in logs and is reported as an invocation error()
    {
        [$result, $logs] = $this->invokeLambda([
            'error' => true,
        ]);

        $this->assertInvocationError(
            $result,
            $logs,
            'ArgumentCountError',
            'strlen() expects exactly 1 parameter, 0 given',
            '#0 /var/task/tests/Sam/Php/function.php(39)'
        );
    }

    public function test fatal error appears in logs and is reported as an invocation error()
    {
        [$result, $logs] = $this->invokeLambda([
            'fatal_error' => true,
        ]);

        // We don't assert on complete exception traces because they will change over time
        $expectedLogs = <<<LOGS
Fatal error: require(): Failed opening required 'foo' (include_path='.:/opt/bref/lib/php') in /var/task/tests/Sam/Php/function.php on line
LOGS;
        self::assertStringContainsString($expectedLogs, $logs);

        // Check the exception is returned as the lambda result
        // TODO SAM local has a bug at the moment and truncates the output on fatal errors
//        self::assertSame([
//            'errorType',
//            'errorMessage',
//        ], array_keys($result));
//        self::assertSame('Runtime.ExitError', $result['errorType']);
//        self::assertContains('Error: Runtime exited without providing a reason', $result['errorMessage']);
    }

    public function test warnings are logged()
    {
        [$result, $logs] = $this->invokeLambda([
            'warning' => true,
        ]);

        // The warning does not turn the execution into an error
        $this->assertEquals('Hello world', $result);
        // But it appears in the logs
        $this->assertStringContainsString('Warning: This is a test warning in /var/task/tests/Sam/Php/function.php', $logs);
    }

    public function test php extensions()
    {
        [$result, $logs] = $this->invokeLambda([
            'extensions' => true,
        ]);
        sort($result);

        self::assertEquals([
            'Core',
            'PDO',
            'Phar',
            'Reflection',
            'SPL',
            'SimpleXML',
            'Zend OPcache',
            'bcmath',
            'ctype',
            'curl',
            'date',
            'dom',
            'exif',
            'fileinfo',
            'filter',
            'ftp',
            'gettext',
            'hash',
            'iconv',
            'json',
            'libxml',
            'mbstring',
            'mysqli',
            'mysqlnd',
            'openssl',
            'pcntl',
            'pcre',
            'pdo_mysql',
            'pdo_sqlite',
            'posix',
            'readline',
            'session',
            'soap',
            'sockets',
            'sodium',
            'sqlite3',
            'standard',
            'tokenizer',
            'xml',
            'xmlreader',
            'xmlwriter',
            'xsl',
            'zip',
            'zlib',
        ], $result, $logs);
    }

    /**
     * Check some PHP config values
     */
    public function test php config()
    {
        [$result, $logs] = $this->invokeLambda([
            'php-config' => true,
        ]);

        self::assertArraySubset([
            // On the CLI we want errors to be sent to stdout -> those will end up in CloudWatch
            'display_errors' => '1',
            // This means `stderr` in php-cli (http://php.net/manual/errorfunc.configuration.php#ini.error-log)
            'error_log' => null,
            // This is the default production value
            'error_reporting' => (string) (E_ALL & ~E_DEPRECATED & ~E_STRICT),
            'extension_dir' => '/opt/bref/lib/php/extensions/no-debug-zts-20190902',
            // No need for HTML formatting on the CLI
            'html_errors' => '0',
            // Let lambda deal with the max execution time
            'max_execution_time' => '0',
            // Use the max amount of memory possibly available, lambda will limit us
            'memory_limit' => '3008M',
            'opcache.enable' => '1',
            'opcache.enable_cli' => '1',
            'opcache.enable_file_override' => '0',
            'opcache.file_cache' => '/tmp',
            'opcache.file_cache_consistency_checks' => '1',
            'opcache.file_cache_only' => '1',
            'opcache.max_accelerated_files' => '10000',
            'opcache.memory_consumption' => '128',
            // This is to make sure that we don't strip comments from source code since it would break annotations
            'opcache.save_comments' => '1',
            // The code is readonly on lambdas so it never changes
            'opcache.validate_timestamps' => '0',
            'short_open_tag' => '',
            'zend.assertions' => '-1',
            'zend.enable_gc' => '1',
        ], $result, false, $logs);
    }

    public function test environment variables()
    {
        [$result, $logs] = $this->invokeLambda([
            'env' => true,
        ]);

        self::assertEquals([
            '$_ENV' => 'bar',
            '$_SERVER' => 'bar',
            'getenv' => 'bar',
        ], $result, $logs);
    }

    /**
     * @param mixed $event
     */
    private function invokeLambda($event = null): array
    {
        // Use `sam local invoke` because `sam local start-lambda` does not support
        // fetching the logs (that means we can't do assertions on those logs…)
        $command = ['sam', 'local', 'invoke', 'PhpFunction', '--region', 'us-east-1'];
        if ($event === null) {
            $command[] = '--no-event';
        } else {
            $command[] = '--event';
            $command[] = '-';
        }
        $process = new Process($command);
        $process->setWorkingDirectory(__DIR__);
        $process->setTimeout(0);
        $process->setTty(false);
        if ($event !== null) {
            $process->setInput(json_encode($event, JSON_THROW_ON_ERROR));
        }
        $process->mustRun();

        // Cleanup colors from stderr
        $stderr = $process->getErrorOutput();
        $stderr = preg_replace('/\x1b\[[0-9;]*m/', '', $stderr);

        // Extract the result from stdout
        $output = explode("\n", trim($process->getOutput()));
        $lastLine = end($output);
        if (! empty($lastLine)) {
            $result = json_decode($lastLine, true, 512, JSON_THROW_ON_ERROR);
            if (json_last_error()) {
                throw new Exception(json_last_error_msg());
            }
        } else {
            $result = null;
            // Was there an error?
            preg_match('/REPORT RequestId: [^\n]*(.*)/s', $stderr, $matches);
            $error = trim($matches[1] ?? '');
            if ($error !== '') {
                $result = json_decode($error, true, 512, JSON_THROW_ON_ERROR);
                if (json_last_error()) {
                    throw new Exception(json_last_error_msg());
                }
            }
        }

        // Extract the logs from stderr
        $return = preg_match('/START RequestId: .*REPORT RequestId: [^\n]*/s', $stderr, $matches);
        $logs = $return ? $matches[0] : $stderr;

        return [$result, $logs];
    }

    private function assertInvocationError(
        array $invocationResult,
        string $logs,
        string $errorClass,
        string $errorMessage,
        string $stackTraceStartsWith = '#0 /var/task/'
    ): void {
        $this->assertSame([
            'errorType',
            'errorMessage',
            'stackTrace',
        ], array_keys($invocationResult));
        $this->assertEquals($errorClass, $invocationResult['errorType']);
        $this->assertEquals($errorMessage, $invocationResult['errorMessage']);
        $this->assertIsArray($invocationResult['stackTrace']);
        $this->assertStringStartsWith($stackTraceStartsWith, $invocationResult['stackTrace'][0]);

        $this->assertErrorInLogs($logs, $errorClass, $errorMessage, $stackTraceStartsWith);
    }

    private function assertErrorInLogs(
        string $logs,
        string $errorClass,
        string $errorMessage,
        string $stackTraceStartsWith = '#0 /var/task/'
    ): void {
        // Extract the only interesting log line
        $logLines = explode("\n", $logs);
        $logLines = array_filter($logLines, function (string $line): bool {
            $line = trim($line);
            return $line !== ''
                && (strpos($line, 'START') !== 0)
                && (strpos($line, 'END') !== 0)
                && (strpos($line, 'REPORT') !== 0);
        });
        $this->assertCount(1, $logLines);
        $logLine = reset($logLines);

        // Decode the logs from stdout
        [$requestId, $message, $json] = explode("\t", $logLine);

        // Check the request ID matches a UUID
        $this->assertMatchesRegularExpression('/[0-9a-fA-F]{8}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{4}\-[0-9a-fA-F]{12}/', $requestId);

        $this->assertSame('Invoke Error', $message);

        $invocationResult = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'errorType',
            'errorMessage',
            'stack',
        ], array_keys($invocationResult));
        $this->assertEquals($errorClass, $invocationResult['errorType']);
        $this->assertEquals($errorMessage, $invocationResult['errorMessage']);
        $this->assertIsArray($invocationResult['stack']);
        $this->assertStringStartsWith($stackTraceStartsWith, $invocationResult['stack'][0]);
    }
}
