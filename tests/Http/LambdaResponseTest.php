<?php declare(strict_types=1);

namespace Bref\Test\Http;

use Bref\Http\LambdaResponse;
use GuzzleHttp\Psr7\Response as Psr7Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response as SfResponse;

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

    /**
     * @dataProvider Psr7Provider
     */
    public function testFromPsr7Response(ResponseInterface $psr7Response, array $output)
    {
        $response = LambdaResponse::fromPsr7Response($psr7Response);
        self::assertSame($output, $response->toApiGatewayFormat());
    }

    public function psr7Provider()
    {
        yield 'It can convert PSR7 request' => [
            new Psr7Response(404, ['Content-Type' => 'application/json'], json_encode(['foo' => 'bar'])),
            [
                'isBase64Encoded' => false,
                'statusCode' => 404,
                'headers' => [
                    'content-type' => 'application/json',
                ],
                'body' => json_encode(['foo' => 'bar']),
            ],
        ];
        yield 'Nested arrays in headers are flattened' => [
            new Psr7Response(204, ['Foo' => ['Bar', 'baz']]),
            [
                'isBase64Encoded' => false,
                'statusCode' => 204,
                'headers' => ['foo' => 'Bar; baz'],
                'body' => '',
            ],
        ];
    }

    /**
     * @dataProvider symfonyProvider
     */
    public function testFromSymfonyResponse(SfResponse $sfResponse, array $output)
    {
        $response = LambdaResponse::fromSymfonyResponse($sfResponse);
        $apiGatewayFormat = $response->toApiGatewayFormat();

        // Remove Symfony's automatic headers
        unset($apiGatewayFormat['headers']['cache-control']);
        unset($apiGatewayFormat['headers']['date']);

        self::assertSame($output, $apiGatewayFormat);
    }

    public function symfonyProvider()
    {
        yield 'It can convert PSR7 request' => [
            SfResponse::create(json_encode(['Foo' => 'bar']), 404, ['Content-Type' => 'application/json']),
            [
                'isBase64Encoded' => false,
                'statusCode' => 404,
                'headers' => [
                    'content-type' => 'application/json',
                ],
                'body' => json_encode(['Foo' => 'bar']),
            ],
        ];
        yield 'Nested arrays in headers are flattened' => [
            SfResponse::create('', 204, ['Foo' => ['Bar', 'baz']]),
            [
                'isBase64Encoded' => false,
                'statusCode' => 204,
                'headers' => ['foo' => 'Bar; baz'],
                'body' => '',
            ],
        ];
    }

    public function test empty headers are considered objects()
    {
        $response = LambdaResponse::fromPsr7Response(new Psr7Response(204));

        // Make sure that the headers are `"headers":{}` (object) and not `"headers":[]` (array)
        self::assertEquals('{"isBase64Encoded":false,"statusCode":204,"headers":{},"body":""}', json_encode($response->toApiGatewayFormat()));
    }
}
