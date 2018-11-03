<?php
declare(strict_types=1);

namespace Bref\Test\Http;

use Bref\Http\LambdaResponse;
use Bref\Http\LambdaResponseArraySerializer;
use PHPUnit\Framework\TestCase;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class LambdaResponseArraySerializerTest extends TestCase
{
    /**
     * @return LambdaResponseArraySerializer
     */
    private function buildSerializerWithMultiValueHeaders(): LambdaResponseArraySerializer
    {
        return new LambdaResponseArraySerializer(true);
    }

    /**
     * @return LambdaResponseArraySerializer
     */
    private function buildSerializerWithSingleValueHeaders(): LambdaResponseArraySerializer
    {
        return new LambdaResponseArraySerializer(false);
    }

    /**
     * @test
     */
    public function test I can create a response from HTML content()
    {
        $serializer = $this->buildSerializerWithSingleValueHeaders();

        $response = $serializer(new HtmlResponse('<p>Hello world!</p>'));
        $this->assertArrayPayload($response, [
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
        $serializer = $this->buildSerializerWithSingleValueHeaders();

        $psr7Response = new JsonResponse(['foo' => 'bar'], 404);

        $response = $serializer($psr7Response);
        $this->assertArrayPayload($response, [
            'isBase64Encoded' => false,
            'statusCode' => 404,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['foo' => 'bar']),
        ]);
    }

    /**
     * @test
     */
    public function test nested arrays in headers are flattened()
    {
        $serializer = $this->buildSerializerWithSingleValueHeaders();

        $psr7Response = new EmptyResponse;
        $psr7Response = $psr7Response->withHeader('foo', ['bar', 'baz']);

        $response = $serializer($psr7Response);
        $this->assertArrayPayload($response, [
            'isBase64Encoded' => false,
            'statusCode' => 204,
            'headers' => [
                'Foo' => 'baz',
            ],
            'body' => '',
        ]);
    }

    /**
     * @test
     */
    public function test multivalueheader defaulthandling()
    {
        $serializer = new LambdaResponseArraySerializer();

        $psr7Response = new EmptyResponse;
        $psr7Response = $psr7Response->withHeader('foo', ['bar', 'baz']);

        $response = $serializer($psr7Response);
        $this->assertArrayPayload($response, [
            'isBase64Encoded' => false,
            'statusCode' => 204,
            'multiValueHeaders' => [
                'Foo' => ['bar', 'baz']
            ],
            'body' => '',
        ]);
    }

    /**
     * @test
     */
    public function test nested arrays in multivalueheaders()
    {
        $serializer = $this->buildSerializerWithMultiValueHeaders();

        $psr7Response = new EmptyResponse;
        $psr7Response = $psr7Response->withHeader('foo', ['bar', 'baz']);

        $response = $serializer($psr7Response);
        $this->assertArrayPayload($response, [
            'isBase64Encoded' => false,
            'statusCode' => 204,
            'multiValueHeaders' => [
                'Foo' => ['bar', 'baz']
            ],
            'body' => '',
        ]);
    }

    /**
     * @test
     */
    public function test empty headers are considered objects()
    {
        $serializer = $this->buildSerializerWithSingleValueHeaders();

        $response = $serializer(new EmptyResponse);

        // Make sure that the headers are `"headers":{}` (object) and not `"headers":[]` (array)
        self::assertEquals('{"isBase64Encoded":false,"statusCode":204,"headers":{},"body":""}', json_encode($response));
    }

    /**
     * @test
     */
    public function test empty multivalueheaders are considered objects()
    {
        $serializer = $this->buildSerializerWithMultiValueHeaders();

        $response = $serializer(new EmptyResponse);

        // Make sure that the multi-value headers are `"multiValueHeaders":{}` (object) and not `"multiValueHeaders":[]` (array)
        self::assertEquals('{"isBase64Encoded":false,"statusCode":204,"multiValueHeaders":{},"body":""}', json_encode($response));
    }


    private function assertArrayPayload(array $response, array $expected)
    {
        self::assertEquals($expected, $response);
    }
}
