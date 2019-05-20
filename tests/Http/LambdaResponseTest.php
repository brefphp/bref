<?php declare(strict_types=1);

namespace Bref\Test\Http;

use Bref\Http\LambdaResponse;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class LambdaResponseTest extends TestCase
{
    public function test I can create a response from HTML content()
    {
        $response = LambdaResponse::fromHtml('<p>Hello world!</p>');
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
            'body' => '<p>Hello world!</p>',
        ], $response->toResponseFormat());
    }

    public function test I can create a response from a PSR7 response()
    {
        $psr7Response = new JsonResponse(['foo' => 'bar'], 404);

        $response = LambdaResponse::fromPsr7Response($psr7Response);
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 404,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['foo' => 'bar']),
        ], $response->toResponseFormat());
    }

    public function test nested arrays in headers are flattened()
    {
        $psr7Response = new EmptyResponse;
        $psr7Response = $psr7Response->withHeader('foo', ['bar', 'baz']);

        $response = LambdaResponse::fromPsr7Response($psr7Response);
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 204,
            'headers' => ['Foo' => 'baz'],
            'body' => '',
        ], $response->toResponseFormat());
    }

    public function test empty headers are considered objects()
    {
        $response = LambdaResponse::fromPsr7Response(new EmptyResponse);

        // Make sure that the headers are `"headers":{}` (object) and not `"headers":[]` (array)
        self::assertEquals('{"isBase64Encoded":false,"statusCode":204,"headers":{},"body":""}', json_encode($response->toResponseFormat()));
    }
}
