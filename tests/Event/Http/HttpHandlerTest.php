<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * Tests the HTTP handler in streamed mode (when `BREF_STREAMED_MODE` is enabled).
 */
class HttpHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('BREF_STREAMED_MODE=1');
    }

    protected function tearDown(): void
    {
        putenv('BREF_STREAMED_MODE=0');
        parent::tearDown();
    }

    public function test streamed response in API Gateway v1()
    {
        $handler = $this->createHandler('<p>Hello world!</p>');

        $result = $handler->handle(
            json_decode(file_get_contents(__DIR__ . '/Fixture/ag-v1-simple.json'), true, 512, JSON_THROW_ON_ERROR),
            Context::fake()
        );

        self::assertInstanceOf(Generator::class, $result);

        self::assertSame([
            'statusCode' => 200,
            'headers' => [],
        ], json_decode($result->current(), true, 512, JSON_THROW_ON_ERROR));

        $result->next();
        self::assertSame("\0\0\0\0\0\0\0\0", $result->current());

        $result->next();
        self::assertSame('<p>Hello world!</p>', $result->current());
    }

    public function test streamed response in API Gateway v2()
    {
        $handler = $this->createHandler('<p>Hello world!</p>');

        $result = $handler->handle(
            json_decode(file_get_contents(__DIR__ . '/Fixture/ag-v2-simple.json'), true, 512, JSON_THROW_ON_ERROR),
            Context::fake()
        );

        self::assertInstanceOf(Generator::class, $result);

        self::assertSame([
            'cookies' => [],
            'statusCode' => 200,
            'headers' => [],
        ], json_decode($result->current(), true, 512, JSON_THROW_ON_ERROR));

        $result->next();
        self::assertSame("\0\0\0\0\0\0\0\0", $result->current());

        $result->next();
        self::assertSame('<p>Hello world!</p>', $result->current());
    }

    public function test warmer invocations are handled()
    {
        $handler = $this->createHandler('');

        $result = $handler->handle(['warmer' => true], Context::fake());

        self::assertSame(['Lambda is warm'], $result);
    }

    private function createHandler(string $body): HttpHandler
    {
        return new class($body) extends HttpHandler {
            private string $body;

            public function __construct(string $body)
            {
                $this->body = $body;
            }

            public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
            {
                return new HttpResponse($this->body);
            }
        };
    }
}
