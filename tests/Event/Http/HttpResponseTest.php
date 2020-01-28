<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpResponse;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class HttpResponseTest extends TestCase
{
    public function test conversion to API Gateway format()
    {
        $response = new HttpResponse(
            200,
            [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
            '<p>Hello world!</p>'
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

    public function test I can create a response from a PSR7 response()
    {
        $psr7Response = new JsonResponse(['foo' => 'bar'], 404);

        $response = HttpResponse::fromPsr7Response($psr7Response);
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 404,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['foo' => 'bar']),
        ], $response->toApiGatewayFormat());
    }

    public function test nested arrays in headers are flattened()
    {
        $psr7Response = new EmptyResponse;
        $psr7Response = $psr7Response->withHeader('foo', ['bar', 'baz']);

        $response = HttpResponse::fromPsr7Response($psr7Response);
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 204,
            'headers' => ['Foo' => 'baz'],
            'body' => '',
        ], $response->toApiGatewayFormat());
    }

    public function test empty headers are considered objects()
    {
        $response = HttpResponse::fromPsr7Response(new EmptyResponse);

        // Make sure that the headers are `"headers":{}` (object) and not `"headers":[]` (array)
        self::assertEquals('{"isBase64Encoded":false,"statusCode":204,"headers":{},"body":""}', json_encode($response->toApiGatewayFormat()));
    }

    /**
     * @see https://github.com/brefphp/bref/issues/534
     */
    public function test header values are forced as arrays for multiheaders()
    {
        $psr7Response = new EmptyResponse;
        $psr7Response = $psr7Response->withHeader('foo', 'bar');

        $response = HttpResponse::fromPsr7Response($psr7Response);
        self::assertEquals([
            'isBase64Encoded' => false,
            'statusCode' => 204,
            'multiValueHeaders' => [
                'Foo' => ['bar'],
            ],
            'body' => '',
        ], $response->toApiGatewayFormat(true));
    }
}
