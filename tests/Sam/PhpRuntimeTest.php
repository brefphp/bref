<?php declare(strict_types=1);

namespace Bref\Test\Sam;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class PhpRuntimeTest extends TestCase
{
    public function test invocation without event()
    {
        [$result, $stderr] = $this->invokeLambda();

        self::assertEquals('Hello world', $result, $stderr);
    }

    public function test invocation with event()
    {
        [$result, $stderr] = $this->invokeLambda([
            'name' => 'Abby',
        ]);

        self::assertEquals('Hello Abby', $result, $stderr);
    }

    public function test error_log function()
    {
        [$result, $stderr] = $this->invokeLambda([
            'error_log' => true,
        ]);

        self::assertNotContains('This is a test log from error_log', $result);
        self::assertContains('This is a test log from error_log', $stderr);
    }

    public function test php extensions()
    {
        [$result, $stderr] = $this->invokeLambda([
            'extensions' => true,
        ]);

        self::assertEquals([
            'Core',
            'date',
            'libxml',
            'openssl',
            'pcre',
            'sqlite3',
            'zlib',
            'ctype',
            'curl',
            'dom',
            'hash',
            'fileinfo',
            'filter',
            'ftp',
            'gettext',
            'SPL',
            'iconv',
            'json',
            'mbstring',
            'pcntl',
            'session',
            'PDO',
            'pdo_sqlite',
            'standard',
            'posix',
            'readline',
            'Reflection',
            'Phar',
            'SimpleXML',
            'sodium',
            'exif',
            'tokenizer',
            'xml',
            'xmlreader',
            'xmlwriter',
            'zip',
            'mysqlnd',
            'Zend OPcache',
        ], $result, $stderr);
    }

    /**
     * Check some PHP config values
     */
    public function test php config()
    {
        [$result, $stderr] = $this->invokeLambda([
            'php-config' => true,
        ]);

        self::assertArraySubset([
            // On the CLI we want errors to be sent to stdout -> those will end up in CloudWatch
            'display_errors' => '1',
            // This means `stderr` in php-cli (http://php.net/manual/errorfunc.configuration.php#ini.error-log)
            'error_log' => null,
            // This is the default production value
            'error_reporting' => (string) (E_ALL & ~E_DEPRECATED & ~E_STRICT),
            'extension_dir' => '/opt/bref/lib/php/extensions/no-debug-zts-20180731',
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
        ], $result, $stderr);
    }

    /**
     * @param mixed $event
     */
    private function invokeLambda($event = null): array
    {
        $command = ['sam', 'local', 'invoke', 'PhpFunction', '--region', 'us-east-1'];
        if ($event === null) {
            $command[] = '--no-event';
        }
        $process = new Process($command);
        $process->setWorkingDirectory(__DIR__);
        $process->setTimeout(0);
        if ($event !== null) {
            $process->setInput(json_encode($event));
        }
        $process->mustRun();

        $output = explode("\n", trim($process->getOutput()));
        $lastLine = end($output);
        $result = json_decode($lastLine, true);

        return [$result, $process->getErrorOutput()];
    }
}
