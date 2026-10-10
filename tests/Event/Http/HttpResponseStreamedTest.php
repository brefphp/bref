<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpResponse;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * Tests the Lambda streaming response format.
 *
 * These tests cover streamed mode only (when `BREF_STREAMED_MODE` is enabled).
 */
class HttpResponseStreamedTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        putenv('BREF_STREAMED_MODE=1');
    }

    protected function tearDown(): void
    {
        putenv('BREF_STREAMED_MODE=0');
        parent::tearDown();
    }

    public function test streamed response in API Gateway v1 format()
    {
        $response = new HttpResponse('<p>Hello world!</p>', [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);

        $generator = $response->toApiGatewayFormat();
        self::assertInstanceOf(Generator::class, $generator);

        self::assertSame([
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
        ], json_decode($generator->current(), true, 512, JSON_THROW_ON_ERROR));

        $generator->next();
        self::assertSame("\0\0\0\0\0\0\0\0", $generator->current());

        $generator->next();
        self::assertSame('<p>Hello world!</p>', $generator->current());
    }

    public function test streamed response in API Gateway v2 format()
    {
        $response = new HttpResponse('<p>Hello world!</p>', [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);

        $generator = $response->toApiGatewayFormatV2();
        self::assertInstanceOf(Generator::class, $generator);

        self::assertSame([
            'cookies' => [],
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
        ], json_decode($generator->current(), true, 512, JSON_THROW_ON_ERROR));

        $generator->next();
        self::assertSame("\0\0\0\0\0\0\0\0", $generator->current());

        $generator->next();
        self::assertSame('<p>Hello world!</p>', $generator->current());
    }

    public function test streamed response with multi value headers()
    {
        $response = new HttpResponse('', [
            'foo' => ['bar', 'baz'],
        ]);

        $generator = $response->toApiGatewayFormat(true);
        self::assertInstanceOf(Generator::class, $generator);

        self::assertSame([
            'statusCode' => 200,
            'multiValueHeaders' => [
                'Foo' => ['bar', 'baz'],
            ],
        ], json_decode($generator->current(), true, 512, JSON_THROW_ON_ERROR));

        $generator->next();
        self::assertSame("\0\0\0\0\0\0\0\0", $generator->current());

        $generator->next();
        self::assertSame('', $generator->current());
    }

    public function test streamed response with cookies()
    {
        $response = new HttpResponse('', [
            'set-cookie' => ['foo', 'bar'],
        ]);

        $generator = $response->toApiGatewayFormatV2();
        self::assertInstanceOf(Generator::class, $generator);

        self::assertSame([
            'cookies' => ['foo', 'bar'],
            'statusCode' => 200,
            'headers' => [],
        ], json_decode($generator->current(), true, 512, JSON_THROW_ON_ERROR));
    }

    public static function provideStreamedBody(): iterable
    {
        yield 'single chunk' => [['<p>Hello world!</p>']];
        yield 'multiple chunks' => [['Hello', ' ', 'world!']];
    }

    /**
     * @param array<string> $chunks
     *
     * @dataProvider provideStreamedBody
     */
    public function test streamed response with a generator body in API Gateway v1 format(array $chunks)
    {
        $response = new HttpResponse($this->createBodyGenerator($chunks), [
            'Content-Type' => 'text/html; charset=utf-8',
        ], 201);

        $generator = $response->toApiGatewayFormat();
        self::assertInstanceOf(Generator::class, $generator);

        self::assertSame([
            'statusCode' => 201,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
        ], json_decode($generator->current(), true, 512, JSON_THROW_ON_ERROR));

        $generator->next();
        self::assertSame("\0\0\0\0\0\0\0\0", $generator->current());

        foreach ($chunks as $chunk) {
            $generator->next();
            self::assertSame($chunk, $generator->current());
        }
    }

    /**
     * @param array<string> $chunks
     *
     * @dataProvider provideStreamedBody
     */
    public function test streamed response with a generator body in API Gateway v2 format(array $chunks)
    {
        $response = new HttpResponse($this->createBodyGenerator($chunks), [
            'Content-Type' => 'text/html; charset=utf-8',
        ], 201);

        $generator = $response->toApiGatewayFormatV2();
        self::assertInstanceOf(Generator::class, $generator);

        self::assertSame([
            'cookies' => [],
            'statusCode' => 201,
            'headers' => [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
        ], json_decode($generator->current(), true, 512, JSON_THROW_ON_ERROR));

        $generator->next();
        self::assertSame("\0\0\0\0\0\0\0\0", $generator->current());

        foreach ($chunks as $chunk) {
            $generator->next();
            self::assertSame($chunk, $generator->current());
        }
    }

    /**
     * @param array<string> $chunks
     */
    private function createBodyGenerator(array $chunks): Generator
    {
        foreach ($chunks as $chunk) {
            yield $chunk;
        }
    }
}
