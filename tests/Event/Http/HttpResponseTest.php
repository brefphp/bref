<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

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
    }

    public function test nested arrays in headers are flattened()
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
    }

    public function test empty headers are considered objects()
    {
        $response = new HttpResponse('');

        // Make sure that the headers are `"headers":{}` (object) and not `"headers":[]` (array)
        self::assertEquals('{"isBase64Encoded":false,"statusCode":200,"headers":{},"body":""}', json_encode($response->toApiGatewayFormat()));
    }

    /**
     * @see https://github.com/brefphp/bref/issues/534
     */
    public function test header values are forced as arrays for multiheadersV1()
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

    /**
     * @see https://github.com/brefphp/bref/issues/534
     */
    public function test header values are forced as arrays for multiheadersV2()
    {
        $response = new HttpResponse('', [
            'foo' => 'bar',
        ]);
        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Foo' => ['bar'],
            ],
            'body' => '',
        ], $response->toApiGatewayFormat(true, 2.0));
    }
}
