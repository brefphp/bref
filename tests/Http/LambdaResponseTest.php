<?php
declare(strict_types=1);

namespace PhpLambda\Test\Http;

use PhpLambda\Http\LambdaResponse;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class LambdaResponseTest extends TestCase
{
    /**
     * @test
     */
    public function test I can create a response from HTML content()
    {
        $response = LambdaResponse::fromHtml('<p>Hello world!</p>');
        $this->assertJsonPayload($response, [
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
            'body' => '<p>Hello world!</p>',
        ]);
    }

    /**
     * @test
     */
    public function test I can create a response from a PSR7 response()
    {
        $psr7Response = new JsonResponse(['foo' => 'bar'], 404);

        $response = LambdaResponse::fromPsr7Response($psr7Response);
        $this->assertJsonPayload($response, [
            'isBase64Encoded' => false,
            'statusCode' => 404,
            'headers' => [
                'content-type' => 'application/json',
            ],
            'body' => json_encode(['foo' => 'bar']),
        ]);
    }

    /**
     * @test
     */
    public function test nested arrays in headers are flattened()
    {
        $psr7Response = new EmptyResponse;
        $psr7Response = $psr7Response->withHeader('foo', ['bar', 'baz']);

        $response = LambdaResponse::fromPsr7Response($psr7Response);
        $this->assertJsonPayload($response, [
            'isBase64Encoded' => false,
            'statusCode' => 204,
            'headers' => [
                'foo' => 'bar',
            ],
            'body' => '',
        ]);
    }

    private function assertJsonPayload(LambdaResponse $response, array $expected)
    {
        self::assertEquals($expected, json_decode($response->toJson(), true));
    }
}
