<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpResponse;
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
}
