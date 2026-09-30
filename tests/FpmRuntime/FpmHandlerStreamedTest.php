<?php declare(strict_types=1);

namespace Bref\Test\FpmRuntime;

use Bref\Context\Context;
use Bref\FpmRuntime\FpmHandler;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * These tests cover the streaming of FPM responses (when `BREF_STREAMED_MODE` is enabled).
 */
final class FpmHandlerStreamedTest extends TestCase
{
    private ?FpmHandler $fpm = null;
    private Context $fakeContext;

    public function setUp(): void
    {
        parent::setUp();

        putenv('BREF_STREAMED_MODE=1');

        $this->fakeContext = new Context('abc', time(), 'abc', 'abc');
    }

    public function tearDown(): void
    {
        $this->fpm?->stop();
        putenv('BREF_STREAMED_MODE=0');
    }

    private function startFpm(string $fixture = 'streaming.php'): void
    {
        $this->fpm = new FpmHandler(__DIR__ . "/fixtures/$fixture", __DIR__ . '/fixtures/php-fpm.conf');
        $this->fpm->start();
    }

    /**
     * Returns an API Gateway HTTP API (version 2.0) event.
     */
    private function getHttpApiEvent(): array
    {
        return [
            'version' => '2.0',
            'routeKey' => 'ANY /{proxy+}',
            'rawPath' => '/',
            'rawQueryString' => '',
            'headers' => [],
            'requestContext' => [
                'accountId' => '123456789012',
                'apiId' => 'api-id',
                'domainName' => 'id.execute-api.us-east-1.amazonaws.com',
                'http' => [
                    'method' => 'GET',
                    'path' => '/',
                    'protocol' => 'HTTP/1.1',
                    'sourceIp' => '127.0.0.1',
                    'userAgent' => 'Test',
                ],
                'requestId' => 'id',
                'routeKey' => 'ANY /{proxy+}',
                'stage' => '$default',
                'time' => '12/Mar/2020:19:03:58 +0000',
                'timeEpoch' => 1583348638391,
            ],
        ];
    }

    public function test streamed response is returned as a generator()
    {
        $this->startFpm();

        $result = $this->fpm->handle($this->getHttpApiEvent(), $this->fakeContext);

        self::assertInstanceOf(Generator::class, $result);
    }

    public function test streamed response sends chunks incrementally()
    {
        $this->startFpm();

        $result = $this->fpm->handle($this->getHttpApiEvent(), $this->fakeContext);
        self::assertInstanceOf(Generator::class, $result);

        // The first yield of the streamed format is the response metadata (status code and headers)
        $result->rewind();
        $metadata = json_decode((string) $result->current(), true, 512, JSON_THROW_ON_ERROR);
        $result->next();

        self::assertSame(201, $metadata['statusCode']);
        self::assertSame('streamed-value', $metadata['headers']['X-Custom-Header']);

        // The body is streamed chunk by chunk: the first chunk arrives as soon as
        // PHP-FPM starts emitting it, while the total response takes longer than
        // one blocking FastCGI read
        $start = microtime(true);
        $firstChunkTime = null;
        $body = '';
        while ($result->valid()) {
            $body .= (string) $result->current();
            $firstChunkTime ??= microtime(true);
            $result->next();
        }
        $totalTime = microtime(true) - $start;

        // The streamed body is prefixed with the null-byte separator of the Lambda Streaming Format
        self::assertSame("\0\0\0\0\0\0\0\0" . 'chunk-1chunk-2chunk-3', $body);
        self::assertLessThan(0.15, $firstChunkTime - $start);
        self::assertGreaterThan(0.25, $totalTime);
    }

    public function test streamed response keeps the response headers()
    {
        $this->startFpm();

        $result = $this->fpm->handle($this->getHttpApiEvent(), $this->fakeContext);
        self::assertInstanceOf(Generator::class, $result);

        $result->rewind();
        $metadata = json_decode((string) $result->current(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('text/plain;charset=UTF-8', $metadata['headers']['Content-Type']);
    }

    public function test response is not streamed when streamed mode is disabled()
    {
        putenv('BREF_STREAMED_MODE=0');

        $this->startFpm();

        $result = $this->fpm->handle($this->getHttpApiEvent(), $this->fakeContext);

        self::assertIsArray($result);
        self::assertSame(201, $result['statusCode']);
        self::assertSame('chunk-1chunk-2chunk-3', $result['body']);
        self::assertSame('streamed-value', $result['headers']['X-Custom-Header']);
    }

    public function test response is not streamed from FPM for non HTTP API events even in streamed mode()
    {
        $this->startFpm();

        // ALB events do not contain `requestContext.http`: the response is not streamed
        // from PHP-FPM, but the streamed format is still applied by the runtime
        $albEvent = [
            'requestContext' => [
                'elb' => ['targetGroupArn' => 'arn:aws:elasticloadbalancing:...'],
            ],
            'httpMethod' => 'GET',
            'path' => '/',
            'headers' => [],
            'body' => '',
            'isBase64Encoded' => false,
        ];

        $result = $this->fpm->handle($albEvent, $this->fakeContext);

        self::assertInstanceOf(Generator::class, $result);

        $result->rewind();
        $metadata = json_decode((string) $result->current(), true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(201, $metadata['statusCode']);

        $result->next();
        self::assertSame("\0\0\0\0\0\0\0\0", $result->current());

        $result->next();
        self::assertSame('chunk-1chunk-2chunk-3', $result->current());
    }
}
