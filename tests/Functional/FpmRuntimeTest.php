<?php declare(strict_types=1);

namespace Bref\Test\Functional;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class FpmRuntimeTest extends TestCase
{
    /** @var Client */
    private $http;

    public function setUp(): void
    {
        parent::setUp();

        $this->http = new Client([
            'base_uri' => 'https://5octfcz6gc.execute-api.eu-west-1.amazonaws.com/dev/',
            'http_errors' => false,
        ]);
    }

    public function test GET()
    {
        $response = $this->http->request('GET');

        $this->assertResponseSuccessful($response);
        self::assertEquals('Hello world!', $this->getBody($response));
    }

    public function test GET with query parameter()
    {
        $response = $this->http->request('GET', '?name=Abby');

        $this->assertResponseSuccessful($response);
        self::assertEquals('Hello Abby', $this->getBody($response));
    }

    public function test stderr do not show in the HTTP response()
    {
        $response = $this->http->request('GET', '?stderr=1');

        $this->assertResponseSuccessful($response);
        self::assertNotContains('This is a test log into stderr', $this->responseAsString($response));
    }

    public function test error_log function()
    {
        $response = $this->http->request('GET', '?error_log=1');

        $this->assertResponseSuccessful($response);
        self::assertNotContains('This is a test log from error_log', $this->responseAsString($response));
    }

    public function test uncaught exception returns a 500 without the details()
    {
        $response = $this->http->request('GET', '?exception=1');

        self::assertSame(500, $response->getStatusCode());
        self::assertNotContains('This is an uncaught exception', $this->responseAsString($response));
    }

    public function test error returns a 500 without the details()
    {
        $response = $this->http->request('GET', '?error=1');

        self::assertSame(500, $response->getStatusCode());
        self::assertNotContains('strlen() expects exactly 1 parameter, 0 given', $this->responseAsString($response));
    }

    public function test fatal error returns a 500 without the details()
    {
        $response = $this->http->request('GET', '?fatal_error=1');

        self::assertSame(500, $response->getStatusCode());
        self::assertNotContains("require(): Failed opening required 'foo'", $this->responseAsString($response));
    }

    public function test warnings do not fail the request and do not appear in the response()
    {
        $response = $this->http->request('GET', '?warning=1');

        $this->assertResponseSuccessful($response);
        self::assertEquals('Hello world!', $this->getBody($response));
        self::assertNotContains('This is a test warning', $this->responseAsString($response));
    }

    public function test php extensions()
    {
        $response = $this->http->request('GET', '?extensions=1');
        $extensions = $this->getJsonBody($response);
        sort($extensions);

        self::assertEquals([
            'Core',
            'PDO',
            'Phar',
            'Reflection',
            'SPL',
            'SimpleXML',
            'Zend OPcache',
            'bcmath',
            'cgi-fcgi',
            'ctype',
            'curl',
            'date',
            'dom',
            'exif',
            'fileinfo',
            'filter',
            'ftp',
            'gd',
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
        ], $extensions);
    }

    /**
     * Check some PHP config values
     */
    public function test php config()
    {
        $response = $this->http->request('GET', '?php-config=1');

        self::assertArraySubset([
            // On PHP-FPM we don't want errors to be sent to stdout because that sends them to the HTTP response
            'display_errors' => '0',
            // This is sent to PHP-FPM, which sends them back to CloudWatch
            'error_log' => null,
            // This is the default production value
            'error_reporting' => (string) (E_ALL & ~E_DEPRECATED & ~E_STRICT),
            'extension_dir' => '/opt/bref/lib/php/extensions/no-debug-zts-20190902',
            // Same limit as API Gateway
            'max_execution_time' => '30',
            'max_input_time' => '60',
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
            // Check POST configuration
            'post_max_size' => '6M',
            'upload_max_filesize' => '6M',
        ], $this->getJsonBody($response), false);
    }

    public function test environment variables()
    {
        $response = $this->http->request('GET', '?env=1');

        self::assertEquals([
            '$_ENV' => 'bar',
            '$_SERVER' => 'bar',
            'getenv' => 'bar',
        ], $this->getJsonBody($response));
    }

    public function test error on invalid URL()
    {
        $response = $this->http->request('GET', 'missing-handler');

        self::assertSame(403, $response->getStatusCode());
        self::assertEquals(['message' => 'Missing Authentication Token'], $this->getJsonBody($response));
    }

    /**
     * The API Gateway limit is 10Mb, but Lambda is 6Mb.
     *
     * @see https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-limits.html
     * We check with 4Mb because this works. 5Mb fails, maybe because the whole size of the event
     * is larger (because of the whole JSON formatting plus headers?).
     */
    public function test max upload size is 6Mb()
    {
        $body4Mb = str_repeat(' ', 1024 * 1024 * 4);
        $response = $this->http->request('POST', '', [
            'body' => $body4Mb,
        ]);
        $this->assertResponseSuccessful($response);
        self::assertEquals('Received 4Mb', $this->getBody($response));
    }

    private function assertResponseSuccessful(ResponseInterface $response): void
    {
        self::assertSame(200, $response->getStatusCode(), $this->getBody($response));
    }

    private function getBody(ResponseInterface $response): string
    {
        return $response->getBody()->__toString();
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
