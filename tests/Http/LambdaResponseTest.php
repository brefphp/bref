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
        ], $response->toApiGatewayFormat());
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
        ], $response->toApiGatewayFormat());
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
        ], $response->toApiGatewayFormat());
    }

    public function test empty headers are considered objects()
    {
        $response = LambdaResponse::fromPsr7Response(new EmptyResponse);

        // Make sure that the headers are `"headers":{}` (object) and not `"headers":[]` (array)
        self::assertEquals('{"isBase64Encoded":false,"statusCode":204,"headers":{},"body":""}', json_encode($response->toApiGatewayFormat()));
    }

    public function test base64 encoding status is taken from headers()
    {
        $encodedResponse = new LambdaResponse(200, ['isbase64encoded' => '1'], '');
        $unencodedResponse = new LambdaResponse(200, ['isbase64encoded' => '0'], '');

        self::assertSame([
            'isBase64Encoded' => true,
            'statusCode' => 200,
            'headers' => ['isbase64encoded' => '1'],
            'body' => '',
        ], $encodedResponse->toApiGatewayFormat());

        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => ['isbase64encoded' => '0'],
            'body' => '',
        ], $unencodedResponse->toApiGatewayFormat());
    }
}
