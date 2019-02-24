<?php declare(strict_types=1);

namespace Bref\Test\Sam;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;

class PhpFpmRuntimeTest extends TestCase
{
    /** @var string */
    private $logs;

    public function setUp()
    {
        parent::setUp();
        $this->logs = '';
    }

    public function test invocation without event()
    {
        $response = $this->invoke('/');

        $this->assertResponseSuccessful($response);
        self::assertEquals('Hello world!', $this->getBody($response), $this->logs);
    }

    public function test invocation with event()
    {
        $response = $this->invoke('/?name=Abby');

        $this->assertResponseSuccessful($response);
        self::assertEquals('Hello Abby', $this->getBody($response), $this->logs);
    }

    public function test stderr ends up in logs()
    {
        $response = $this->invoke('/?stderr=1');

        $this->assertResponseSuccessful($response);
        self::assertNotContains('This is a test log into stderr', $this->responseAsString($response));
        self::assertContains('This is a test log into stderr', $this->logs);
    }

    public function test error_log function()
    {
        $response = $this->invoke('/?error_log=1');

        $this->assertResponseSuccessful($response);
        self::assertNotContains('This is a test log from error_log', $this->responseAsString($response));
        self::assertContains('This is a test log from error_log', $this->logs);
    }

    public function test uncaught exception appears in logs and returns a 500()
    {
        $response = $this->invoke('/?exception=1');

        self::assertSame(500, $response->getStatusCode(), $this->logs);
        self::assertNotContains('This is an uncaught exception', $this->responseAsString($response));
        self::assertContains('Fatal error:  Uncaught Exception: This is an uncaught exception in /var/task/tests/Sam', $this->logs);
    }

    public function test error appears in logs and returns a 500()
    {
        $response = $this->invoke('/?error=1');

        self::assertSame(500, $response->getStatusCode(), $this->logs);
        self::assertNotContains('strlen() expects exactly 1 parameter, 0 given', $this->responseAsString($response));
        self::assertContains('PHP Fatal error:  Uncaught ArgumentCountError: strlen() expects exactly 1 parameter, 0 given in /var/task/tests/Sam', $this->logs);
    }

    public function test fatal error appears in logs()
    {
        $response = $this->invoke('/?fatal_error=1');

        self::assertSame(500, $response->getStatusCode(), $this->logs);
        self::assertNotContains("require(): Failed opening required 'foo'", $this->responseAsString($response));
        $expectedLogs = "PHP Fatal error:  require(): Failed opening required 'foo' (include_path='.:/opt/bref/lib/php') in /var/task/tests/Sam";
        self::assertContains($expectedLogs, $this->logs);
    }

    public function test warnings are logged()
    {
        $response = $this->invoke('/?warning=1');

        $this->assertResponseSuccessful($response);
        self::assertEquals('Hello world!', $this->getBody($response), $this->logs);
        self::assertNotContains('This is a test warning', $this->responseAsString($response));
        self::assertContains('Warning:  This is a test warning in /var/task/tests/Sam', $this->logs);
    }

    public function test php extensions()
    {
        $response = $this->invoke('/?extensions=1');

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
        ], $this->getJsonBody($response), $this->logs);
    }

    /**
     * Check some PHP config values
     */
    public function test php config()
    {
        $response = $this->invoke('/?php-config=1');

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
        ], $this->getJsonBody($response), false, $this->logs);
    }

    private function invoke(string $url): ResponseInterface
    {
        $api = new Process(['sam', 'local', 'start-api', '--region', 'us-east-1']);
        $api->setWorkingDirectory(__DIR__);
        $api->setTimeout(0);
        $api->start();
        $api->waitUntil(function ($type, $output) {
            return strpos($output, 'Running on http://127.0.0.1:3000/') !== false;
        });

        try {
            $http = new Client([
                'base_uri' => 'http://127.0.0.1:3000',
                'http_errors' => false,
            ]);
            $response = $http->request('GET', $url);
        } finally {
            $api->stop();
            $this->logs = $api->getErrorOutput() . $api->getOutput();
        }

        return $response;
    }

    private function assertResponseSuccessful(ResponseInterface $response): void
    {
        self::assertSame(200, $response->getStatusCode(), $this->logs);
    }

    private function getBody(ResponseInterface $response): string
    {
        return $response->getBody()->getContents();
    }

    private function getJsonBody(ResponseInterface $response)
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    private function responseAsString(ResponseInterface $response): string
    {
        $string = '';
        foreach ($response->getHeaders() as $name => $values) {
            $string .= $name . ': ' . implode(', ', $values) . "\n";
        }
        $string .= "\n" . $this->getBody($response) . "\n";

        return $string;
    }
}
