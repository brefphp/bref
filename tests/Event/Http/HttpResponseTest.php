<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpResponse;
use Bref\Runtime\LambdaResult;
use PHPUnit\Framework\TestCase;
use stdClass;

class HttpResponseTest extends TestCase
{
    public function test conversion to API Gateway format()
    {
        $response = new HttpResponse(
            '<p>Hello world!</p>',
            [
                'Content-Type' => 'text/html; charset=utf-8',
            ]
        );
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
            'body' => '<p>Hello world!</p>',
        ], $response->toApiGatewayFormat());

        self::assertSame([
            'cookies' => [],
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
            'body' => '<p>Hello world!</p>',
        ], $response->toApiGatewayFormatV2());
    }

    public function test headers are capitalized()
    {
        $response = new HttpResponse('', [
            'x-foo-bar' => 'baz',
        ]);

        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => ['X-Foo-Bar' => 'baz'],
            'body' => '',
        ], $response->toApiGatewayFormat());

        self::assertEquals([
            'cookies' => [],
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => ['X-Foo-Bar' => 'baz'],
            'body' => '',
        ], $response->toApiGatewayFormatV2());
    }

    public function test multi value headers()
    {
        $response = new HttpResponse('', [
            'foo' => ['bar', 'baz'],
        ]);

        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            // The last value is kept (when multiheaders are not enabled)
            'headers' => ['Foo' => 'baz'],
            'body' => '',
        ], $response->toApiGatewayFormat());

        self::assertEquals([
            'cookies' => [],
            'isBase64Encoded' => false,
            'statusCode' => 200,
            // Headers are joined with a comma
            // See https://datatracker.ietf.org/doc/html/rfc7230#section-3.2.2
            // API Gateway v2 does not support multi-value headers
            'headers' => ['Foo' => 'bar, baz'],
            'body' => '',
        ], $response->toApiGatewayFormatV2());
    }

    public function test empty headers are considered objects()
    {
        $response = new HttpResponse('');

        // Make sure that the headers are `"headers":{}` (object) and not `"headers":[]` (array)
        self::assertEquals('{"isBase64Encoded":false,"statusCode":200,"headers":{},"body":""}', json_encode($response->toApiGatewayFormat()));
        self::assertEquals('{"cookies":[],"isBase64Encoded":false,"statusCode":200,"headers":{},"body":""}', json_encode($response->toApiGatewayFormatV2()));
    }

    /**
     * @see https://github.com/brefphp/bref/issues/534
     */
    public function test header values are forced as arrays for multiheaders()
    {
        $response = new HttpResponse('', [
            'foo' => 'bar',
        ]);
        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'multiValueHeaders' => [
                'Foo' => ['bar'],
            ],
            'body' => '',
        ], $response->toApiGatewayFormat(true));
    }

    public function test response with single cookie()
    {
        $response = new HttpResponse('', [
            'set-cookie' => 'foo',
        ]);

        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Set-Cookie' => 'foo',
            ],
            'body' => '',
        ], $response->toApiGatewayFormat());

        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'multiValueHeaders' => [
                'Set-Cookie' => ['foo'],
            ],
            'body' => '',
        ], $response->toApiGatewayFormat(true));

        self::assertEquals([
            'cookies' => ['foo'],
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => new stdClass,
            'body' => '',
        ], $response->toApiGatewayFormatV2());
    }

    public function test cookies are detected regardless of the header case()
    {
        $response = new HttpResponse('', [
            'SET-COOKIE' => ['foo', 'bar'],
        ]);

        self::assertEquals([
            'cookies' => ['foo', 'bar'],
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => new stdClass,
            'body' => '',
        ], $response->toApiGatewayFormatV2());
    }

    public function test response with multiple cookies()
    {
        $response = new HttpResponse('', [
            'set-cookie' => ['foo', 'bar'],
        ]);

        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                // Keep only the last value in v1 without multi-headers
                'Set-Cookie' => 'bar',
            ],
            'body' => '',
        ], $response->toApiGatewayFormat());

        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'multiValueHeaders' => [
                'Set-Cookie' => ['foo', 'bar'],
            ],
            'body' => '',
        ], $response->toApiGatewayFormat(true));

        self::assertEquals([
            'cookies' => ['foo', 'bar'],
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => new stdClass,
            'body' => '',
        ], $response->toApiGatewayFormatV2());
    }

    public function test streamed format()
    {
        $response = new HttpResponse('<p>Hello world!</p>', [
            'content-type' => 'text/html; charset=utf-8',
            'set-cookie' => ['foo=bar', 'baz=qux'],
        ], 404);

        $streamed = $response->toStreamedResponse();

        $this->assertSame('application/vnd.awslambda.http-integration-response', $streamed->contentType);
        $this->assertSame([
            '{"cookies":["foo=bar","baz=qux"],"statusCode":404,"headers":{"Content-Type":"text\\/html; charset=utf-8"}}' . str_repeat("\0", 8),
            '<p>Hello world!</p>',
        ], $this->chunks($streamed));
    }

    public function test bodies produced progressively()
    {
        $this->assertSame(['Hello ', 'world!'], array_slice($this->chunks((new HttpResponse($this->helloWorld()))->toStreamedResponse()), 1));
        // The usual format joins them
        $this->assertSame('Hello world!', (new HttpResponse($this->helloWorld()))->toApiGatewayFormatV2()['body']);
    }

    public function test binary bodies are streamed as is()
    {
        $binary = random_bytes(256);
        $response = new HttpResponse($binary, ['content-type' => 'image/png']);

        $this->assertSame($binary, $this->chunks($response->toStreamedResponse())[1]);
    }

    public function test streamed format without headers()
    {
        $this->assertStringStartsWith(
            '{"cookies":[],"statusCode":204,"headers":{}}',
            $this->chunks((new HttpResponse('', [], 204))->toStreamedResponse())[0],
        );
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

    /**
     * @return \Generator<string>
     */
    private function helloWorld(): \Generator
    {
        yield 'Hello ';
        yield 'world!';
    }
}
