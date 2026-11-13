<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Bref\Runtime\StreamedResponse;
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

        $this->assertInstanceOf(StreamedResponse::class, $result);
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

        // Nothing is sent: the runtime turns it into a 500 without details (see LambdaRuntimeTest)
        $this->expectExceptionMessage('Secret details');
        $handler->handle($this->event('abc.lambda-url.eu-west-3.on.aws'), $this->context());
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
    private function chunks(StreamedResponse $response): array
    {
        $chunks = [];
        $response->writeTo(function (string $chunk) use (&$chunks) {
            $chunks[] = $chunk;
        });

        return $chunks;
    }
}
