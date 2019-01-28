<?php declare(strict_types=1);

namespace Bref\Test\Sam;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class PhpFpmRuntimeTest extends TestCase
{
    public function test invocation without event()
    {
        [$result, $stderr] = $this->invoke('/');

        self::assertEquals('Hello world!', $result, $stderr);
    }

    public function test invocation with event()
    {
        [$result, $stderr] = $this->invoke('/?name=Abby');

        self::assertEquals('Hello Abby', $result, $stderr);
    }

    public function test stderr ends up in the logs()
    {
        [$result, $stderr] = $this->invoke('/?stderr=1');

        self::assertNotContains('This is a test log into stderr', $result);
        self::assertContains('This is a test log into stderr', $stderr);
    }

    public function test error_log function()
    {
        [$result, $stderr] = $this->invoke('/?error_log=1');

        self::assertNotContains('This is a test log from error_log', $result);
        self::assertContains('This is a test log from error_log', $stderr);
    }

    public function test php extensions()
    {
        [$result, $stderr] = $this->invoke('/?extensions=1');

        $extensions = json_decode($result, true);

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
            'cgi-fcgi',
            'Zend OPcache',
        ], $extensions, $stderr);
    }

    /**
     * Check some PHP config values
     */
    public function test php config()
    {
        [$result, $stderr] = $this->invoke('/?php-config=1');

        $config = json_decode($result, true);

        self::assertArraySubset([
            // On PHP-FPM we don't want errors to be sent to stdout because that sends them to the HTTP response
            'display_errors' => '0',
            // This is sent to PHP-FPM, which sends them back to CloudWatch
            'error_log' => null,
            // This is the default production value
            'error_reporting' => (string) (E_ALL & ~E_DEPRECATED & ~E_STRICT),
            'extension_dir' => '/opt/bref/lib/php/extensions/no-debug-zts-20180731',
            // Same limit as API Gateway
            'max_execution_time' => '30',
            // Use the max amount of memory possibly available, lambda will limit us
            'memory_limit' => '3008M',
            'opcache.enable' => '1',
            'opcache.enable_cli' => '0',
            // Since we have PHP-FPM we don't need the file cache here
            'opcache.file_cache' => null,
            'opcache.max_accelerated_files' => '10000',
            'opcache.memory_consumption' => '128',
            // This is to make sure that we don't strip comments from source code since it would break annotations
            'opcache.save_comments' => '1',
            // The code is readonly on lambdas so it never changes
            'opcache.validate_timestamps' => '0',
            'short_open_tag' => '',
            'zend.assertions' => '-1',
            'zend.enable_gc' => '1',
        ], $config, $stderr);
    }

    private function invoke(string $url): array
    {
        $api = new Process(['sam', 'local', 'start-api', '--region', 'us-east-1']);
        $api->setWorkingDirectory(__DIR__);
        $api->setTimeout(0);
        $api->start();
        $api->waitUntil(function ($type, $output) {
            return strpos($output, 'Running on http://127.0.0.1:3000/') !== false;
        });

        $body = '';

        try {
            $http = new Client([
                'base_uri' => 'http://127.0.0.1:3000',
            ]);
            $response = $http->request('GET', $url);
            $body = $response->getBody()->getContents();
        } catch (\Throwable $e) {
            $stderr = $api->getErrorOutput() . $api->getOutput();

            throw new \Exception($e . PHP_EOL . $stderr);
        } finally {
            $api->stop();
        }

        $stderr = $api->getErrorOutput() . $api->getOutput();

        return [$body, $stderr];
    }
}
