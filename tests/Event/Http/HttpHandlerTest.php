<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Bref\Event\Http\HttpResponseWriter;
use Bref\Runtime\LambdaResult;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HttpHandlerTest extends TestCase
{
    public function setUp(): void
    {
        putenv('BREF_RESPONSE_STREAMING=1');
    }

    public function tearDown(): void
    {
        putenv('BREF_RESPONSE_STREAMING');
    }

    public function test Function URL responses are streamed()
    {
        $handler = $this->handler(fn () => new HttpResponse('Hello world!', ['content-type' => 'text/plain']));

        $result = $handler->handle($this->event('abc.lambda-url.eu-west-3.on.aws'), $this->context());

        $this->assertInstanceOf(LambdaResult::class, $result);
        $this->assertSame([
            '{"cookies":[],"statusCode":200,"headers":{"Content-Type":"text\\/plain"}}' . str_repeat("\0", 8),
            'Hello world!',
        ], $this->chunks($result));
    }

    public function test Function URLs get the usual format without response streaming()
    {
        putenv('BREF_RESPONSE_STREAMING');
        $handler = $this->handler(fn () => new HttpResponse('Hello world!', ['content-type' => 'text/plain']));

        // The default mode of Function URLs (`BUFFERED`) would show an empty body with the streamed format
        $result = $handler->handle($this->event('abc.lambda-url.eu-west-3.on.aws'), $this->context());

        $this->assertIsArray($result);
        $this->assertSame('Hello world!', $result['body']);
    }

    public function test other HTTP events get the usual format()
    {
        $handler = $this->handler(fn () => new HttpResponse('Hello world!', ['content-type' => 'text/plain']));

        $result = $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com'), $this->context());

        $this->assertIsArray($result);
        $this->assertSame('Hello world!', $result['body']);
    }

    public function test errors on Function URLs are thrown before anything is sent()
    {
        $handler = $this->handler(fn () => throw new RuntimeException('Secret details'));

        $result = $handler->handle($this->event('abc.lambda-url.eu-west-3.on.aws'), $this->context());
        $chunks = [];

        // Nothing is sent: the runtime turns it into a 500 without details (see LambdaRuntimeTest)
        try {
            $result->writeTo(function (string $chunk) use (&$chunks) {
                $chunks[] = $chunk;
            });
            $this->fail('The error must be thrown');
        } catch (RuntimeException $e) {
            $this->assertSame('Secret details', $e->getMessage());
        }
        $this->assertSame([], $chunks);
    }

    public function test handlers can write the response as it is produced()
    {
        $handler = new class extends HttpHandler {
            public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
            {
                throw new LogicException('Not used');
            }

            protected function respond(HttpRequestEvent $event, Context $context, HttpResponseWriter $writer): void
            {
                $writer->start(201, ['content-type' => 'text/plain']);
                $writer->capture(function () {
                    echo 'Hello';
                    echo ' world!';
                });
            }
        };

        $streamed = $handler->handle($this->event('abc.lambda-url.eu-west-3.on.aws'), $this->context());
        $this->assertSame([
            '{"cookies":[],"statusCode":201,"headers":{"Content-Type":"text\\/plain"}}' . str_repeat("\0", 8),
            'Hello',
            ' world!',
        ], $this->chunks($streamed));

        $buffered = $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com'), $this->context());
        $this->assertSame(201, $buffered['statusCode']);
        $this->assertSame('Hello world!', $buffered['body']);
    }

    public function test a handler that writes no response fails()
    {
        $handler = new class extends HttpHandler {
            public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
            {
                throw new LogicException('Not used');
            }

            protected function respond(HttpRequestEvent $event, Context $context, HttpResponseWriter $writer): void
            {
            }
        };

        $this->expectExceptionMessage('The HTTP handler did not write any response');
        $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com'), $this->context());
    }

    public function test REST API responses are streamed()
    {
        $handler = $this->handler(fn () => new HttpResponse('Hello world!', ['content-type' => 'text/plain']));
        $event = [
            'resource' => '/',
            'path' => '/',
            'httpMethod' => 'GET',
            'headers' => ['Host' => 'abc.execute-api.eu-west-3.amazonaws.com'],
            'requestContext' => ['apiId' => 'abc', 'domainName' => 'abc.execute-api.eu-west-3.amazonaws.com', 'httpMethod' => 'GET'],
            'body' => null,
            'isBase64Encoded' => false,
        ];

        $result = $handler->handle($event, $this->context());

        $this->assertInstanceOf(LambdaResult::class, $result);
    }

    /**
     * @dataProvider provide events
     */
    public function test which events are streamed(array $event, bool $streamed)
    {
        $this->assertSame($streamed, HttpRequestEvent::isStreamed($event));
        putenv('BREF_RESPONSE_STREAMING');
        $this->assertFalse(HttpRequestEvent::isStreamed($event));
    }

    public static function provide events(): array
    {
        return [
            'Function URL' => [['version' => '2.0', 'requestContext' => ['apiId' => 'abc', 'domainName' => 'abc.lambda-url.eu-west-3.on.aws']], true],
            'REST API' => [['httpMethod' => 'GET', 'requestContext' => ['apiId' => 'abc']], true],
            'HTTP API v2' => [['version' => '2.0', 'requestContext' => ['apiId' => 'abc', 'domainName' => 'abc.execute-api.eu-west-3.amazonaws.com']], false],
            'HTTP API v1' => [['version' => '1.0', 'httpMethod' => 'GET', 'requestContext' => ['apiId' => 'abc']], false],
            'ALB' => [['httpMethod' => 'GET', 'requestContext' => ['elb' => ['targetGroupArn' => 'arn']]], false],
            'WebSocket' => [['requestContext' => ['apiId' => 'abc', 'connectionId' => 'abc=', 'eventType' => 'MESSAGE']], false],
        ];
    }

    private function handler(callable $handleRequest): HttpHandler
    {
        return new class($handleRequest) extends HttpHandler {
            /** @var callable */
            private $handleRequest;

            public function __construct(callable $handleRequest)
            {
                $this->handleRequest = $handleRequest;
            }

            public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
            {
                return ($this->handleRequest)($event, $context);
            }
        };
    }

    private function event(string $domain): array
    {
        return [
            'version' => '2.0',
            'rawPath' => '/',
            'rawQueryString' => '',
            'headers' => ['host' => $domain],
            'requestContext' => [
                'domainName' => $domain,
                'http' => ['method' => 'GET', 'path' => '/', 'protocol' => 'HTTP/1.1', 'sourceIp' => '1.2.3.4'],
            ],
            'isBase64Encoded' => false,
        ];
    }

    private function context(): Context
    {
        return new Context('abc', time(), 'abc', 'abc');
    }

    /**
     * @return string[]
     */
    private function chunks(LambdaResult $response): array
    {
        $chunks = [];
        $response->writeTo(function (string $chunk) use (&$chunks) {
            $chunks[] = $chunk;
        });

        return $chunks;
    }
}
