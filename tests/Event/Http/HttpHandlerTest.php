<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Bref\Event\Http\HttpResponseWriter;
use Bref\Runtime\InMemoryResponseStream;
use Bref\Test\Fixture\CallbackResponseStream;
use Closure;
// phpcs:ignore SlevomatCodingStandard.Namespaces.UnusedUses.UnusedUse -- the coding standard does not read attributes yet
use PHPUnit\Framework\Attributes\DataProvider;
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
        putenv('BREF_ORIGIN_SECRET');
    }

    public function test the response is written streamed or buffered()
    {
        $handler = $this->handler(fn () => new HttpResponse('Hello world!', ['content-type' => 'text/plain']));

        // The runtime chooses, depending on what invoked the function (see LambdaRuntimeTest)
        $this->assertSame([
            '{"cookies":[],"statusCode":200,"headers":{"Content-Type":"text\\/plain"}}' . str_repeat("\0", 8),
            'Hello world!',
        ], $this->streamed($handler, $this->event('abc.lambda-url.eu-west-3.on.aws')));

        $buffered = $handler->handle($this->event('abc.lambda-url.eu-west-3.on.aws'), $this->context());
        $this->assertSame(200, $buffered['statusCode']);
        $this->assertSame('Hello world!', $buffered['body']);
    }

    public function test errors are thrown before anything is sent()
    {
        $handler = $this->handler(fn () => throw new RuntimeException('Secret details'));

        $lambdaResponse = new InMemoryResponseStream;
        $event = $this->event('abc.lambda-url.eu-west-3.on.aws');

        // Nothing is sent: the runtime turns it into a 500 without details (see LambdaRuntimeTest)
        try {
            $handler->writeResponse($event, $this->context(), new HttpResponseWriter($lambdaResponse, $event, streamed: true));
            $this->fail('The error must be thrown');
        } catch (RuntimeException $e) {
            $this->assertSame('Secret details', $e->getMessage());
        }
        $this->assertSame([], $lambdaResponse->chunks);
    }

    public function test handlers can write the response as it is produced()
    {
        $handler = new class extends HttpHandler {
            public function handleRequest(HttpRequestEvent $event, Context $context, HttpResponseWriter $responseWriter): void
            {
                $responseWriter->start(201, ['content-type' => 'text/plain']);
                $responseWriter->capture(function () {
                    echo 'Hello';
                    echo ' world!';
                });
            }
        };

        $this->assertSame([
            '{"cookies":[],"statusCode":201,"headers":{"Content-Type":"text\\/plain"}}' . str_repeat("\0", 8),
            'Hello',
            ' world!',
        ], $this->streamed($handler, $this->event('abc.lambda-url.eu-west-3.on.aws')));

        $buffered = $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com'), $this->context());
        $this->assertSame(201, $buffered['statusCode']);
        $this->assertSame('Hello world!', $buffered['body']);
    }

    public function test a handler that writes no response fails()
    {
        $handler = new class extends HttpHandler {
            public function handleRequest(HttpRequestEvent $event, Context $context, HttpResponseWriter $responseWriter): void
            {
            }
        };

        $this->expectExceptionMessage('The HTTP handler did not write any response');
        $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com'), $this->context());
    }

    public function test warmer events are answered without the application()
    {
        $handler = $this->handler(fn () => throw new RuntimeException('The application must not run'));

        $response = $handler->handle(['warmer' => true], $this->context());

        $this->assertSame(200, $response['statusCode']);
        $this->assertSame('Lambda is warm', $response['body']);
    }

    public function test requests without the origin secret are refused()
    {
        putenv('BREF_ORIGIN_SECRET=s3cr3t');
        $handler = $this->handler(fn () => new HttpResponse('Hello world!'));

        $missing = $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com'), $this->context());
        $wrong = $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com', ['x-bref-origin-secret' => 'wrong']), $this->context());

        foreach ([$missing, $wrong] as $result) {
            $this->assertSame(403, $result['statusCode']);
            $this->assertSame('Forbidden', $result['body']);
        }
    }

    public function test streamed requests without the origin secret are refused()
    {
        putenv('BREF_ORIGIN_SECRET=s3cr3t');
        $handler = $this->handler(fn () => new HttpResponse('Hello world!'));

        $this->assertSame([
            '{"cookies":[],"statusCode":403,"headers":{"Content-Type":"text\\/plain"}}' . str_repeat("\0", 8),
            'Forbidden',
        ], $this->streamed($handler, $this->event('abc.lambda-url.eu-west-3.on.aws')));
    }

    public function test requests with the origin secret reach the application without it()
    {
        putenv('BREF_ORIGIN_SECRET=s3cr3t');
        $headers = null;
        $handler = $this->handler(function (HttpRequestEvent $event) use (&$headers) {
            $headers = $event->getHeaders();
            return new HttpResponse('Hello world!');
        });

        $result = $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com', ['x-bref-origin-secret' => 's3cr3t']), $this->context());

        $this->assertSame(200, $result['statusCode']);
        $this->assertSame(['host' => ['abc.execute-api.eu-west-3.amazonaws.com']], $headers);
    }

    public function test the origin secret is removed from both header lists of REST APIs()
    {
        putenv('BREF_ORIGIN_SECRET=s3cr3t');
        putenv('BREF_RESPONSE_STREAMING');
        $headers = null;
        $handler = $this->handler(function (HttpRequestEvent $event) use (&$headers) {
            $headers = $event->getHeaders();
            return new HttpResponse('Hello world!');
        });
        // REST APIs keep the case of header names, and send them twice
        $event = [
            'resource' => '/',
            'path' => '/',
            'httpMethod' => 'GET',
            'headers' => ['Host' => 'abc.execute-api.eu-west-3.amazonaws.com', 'X-Bref-Origin-Secret' => 's3cr3t'],
            'multiValueHeaders' => ['Host' => ['abc.execute-api.eu-west-3.amazonaws.com'], 'X-Bref-Origin-Secret' => ['s3cr3t']],
            'requestContext' => ['apiId' => 'abc', 'domainName' => 'abc.execute-api.eu-west-3.amazonaws.com', 'httpMethod' => 'GET'],
            'body' => null,
            'isBase64Encoded' => false,
        ];

        $result = $handler->handle($event, $this->context());

        $this->assertSame(200, $result['statusCode']);
        $this->assertSame(['host' => ['abc.execute-api.eu-west-3.amazonaws.com']], $headers);
    }

    public function test the query string the CDN had to encode reaches the application as it was sent()
    {
        putenv('BREF_ORIGIN_SECRET=s3cr3t');
        $received = null;
        $handler = $this->handler(function (HttpRequestEvent $event) use (&$received) {
            $received = [$event->getQueryString(), $event->getHeaders()];
            return new HttpResponse('Hello world!');
        });
        $event = $this->event('abc.lambda-url.eu-west-3.on.aws', [
            'x-bref-origin-secret' => 's3cr3t',
            'x-bref-raw-query-string' => 'filter[status]=active',
        ]);
        $event['rawQueryString'] = 'filter%5Bstatus%5D=active';

        $this->streamed($handler, $event);

        $this->assertSame(['filter[status]=active', ['host' => ['abc.lambda-url.eu-west-3.on.aws']]], $received);
    }

    public function test the raw query string header is ignored without the origin secret variable()
    {
        $received = null;
        $handler = $this->handler(function (HttpRequestEvent $event) use (&$received) {
            $received = [$event->getQueryString(), $event->getHeaders()];
            return new HttpResponse('Hello world!');
        });
        // Without a CDN that sets it, anyone could send it
        $event = $this->event('abc.execute-api.eu-west-3.amazonaws.com', ['x-bref-raw-query-string' => 'admin=1']);
        $event['rawQueryString'] = 'page=2';

        $handler->handle($event, $this->context());

        $this->assertSame(['page=2', ['host' => ['abc.execute-api.eu-west-3.amazonaws.com']]], $received);
    }

    public function test without the origin secret variable every request is answered()
    {
        $handler = $this->handler(fn () => new HttpResponse('Hello world!'));

        $result = $handler->handle($this->event('abc.execute-api.eu-west-3.amazonaws.com'), $this->context());

        $this->assertSame(200, $result['statusCode']);
    }

    #[DataProvider('provide events')]
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

    public function test a response can end before the handler finishes()
    {
        $log = [];
        $handler = new class(function () use (&$log) {
            $log[] = 'after the end';
        }) extends HttpHandler {
            public function __construct(private Closure $afterEnd)
            {
            }

            public function handleRequest(HttpRequestEvent $event, Context $context, HttpResponseWriter $responseWriter): void
            {
                $responseWriter->start(200, []);
                $responseWriter->write('Hello');
                $responseWriter->end();
                // E.g. terminate callbacks
                ($this->afterEnd)();
            }
        };
        $event = $this->event('abc.lambda-url.eu-west-3.on.aws');

        foreach ([true, false] as $streamed) {
            $log = [];
            $lambdaResponse = new CallbackResponseStream(function () {
            }, function () use (&$log) {
                $log[] = 'end';
            });

            $handler->writeResponse($event, $this->context(), new HttpResponseWriter($lambdaResponse, $event, $streamed));

            // Streamed or not, the client gets the response before the rest of the handler runs
            $this->assertSame(['end', 'after the end'], $log, $streamed ? 'streamed' : 'buffered');
        }
    }

    /**
     * @param callable(HttpRequestEvent, Context): HttpResponse $respond
     */
    private function handler(callable $respond): HttpHandler
    {
        return new class($respond) extends HttpHandler {
            /** @var callable */
            private $respond;

            public function __construct(callable $respond)
            {
                $this->respond = $respond;
            }

            public function handleRequest(HttpRequestEvent $event, Context $context, HttpResponseWriter $responseWriter): void
            {
                ($this->respond)($event, $context)->sendTo($responseWriter);
            }
        };
    }

    private function event(string $domain, array $headers = []): array
    {
        return [
            'version' => '2.0',
            'rawPath' => '/',
            'rawQueryString' => '',
            'headers' => ['host' => $domain, ...$headers],
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
     * The chunks sent to Lambda when the response is streamed.
     *
     * @return string[]
     */
    private function streamed(HttpHandler $handler, array $event): array
    {
        $lambdaResponse = new InMemoryResponseStream;
        $responseWriter = new HttpResponseWriter($lambdaResponse, $event, streamed: true);

        $handler->writeResponse($event, $this->context(), $responseWriter);
        $responseWriter->end();

        return $lambdaResponse->chunks;
    }
}
