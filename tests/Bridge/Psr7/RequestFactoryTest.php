<?php
declare(strict_types=1);

namespace Bref\Test\Bridge\Psr7;

use Bref\Bridge\Psr7\RequestFactory;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase
{
    public function test create basic request()
    {
        $currentTimestamp = time();

        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'GET',
            'queryStringParameters' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            'requestContext' => [
                'protocol' => '1.1',
                'path' => '/test',
                'requestTimeEpoch' => $currentTimestamp,
            ],
            'headers' => [
            ],
        ]);

        self::assertEquals('GET', $request->getMethod());
        self::assertEquals(['foo' => 'bar', 'bim' => 'baz'], $request->getQueryParams());
        self::assertEquals('1.1', $request->getProtocolVersion());
        self::assertEquals('/test', $request->getUri()->__toString());
        self::assertEquals('', $request->getBody()->getContents());
        self::assertEquals([], $request->getAttributes());
        $serverParams = $request->getServerParams();
        unset($serverParams['DOCUMENT_ROOT']);
        self::assertEquals([
            'SERVER_PROTOCOL' => '1.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => $currentTimestamp,
            'QUERY_STRING' => 'foo=bar&bim=baz',
            'REQUEST_URI' => '/test',
        ], $serverParams);
        self::assertEquals('/test', $request->getRequestTarget());
        self::assertEquals([], $request->getHeaders());
    }

    public function test non empty body()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'GET',
            'body' => 'test test test',
        ]);

        self::assertEquals('test test test', $request->getBody()->getContents());
    }

    public function test POST body is parsed()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => 'foo=bar&bim=baz',
        ]);
        self::assertEquals('POST', $request->getMethod());
        self::assertEquals(['foo' => 'bar', 'bim' => 'baz'], $request->getParsedBody());
    }

    public function test the content type header is not case sensitive()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'POST',
            'headers' => [
                // content-type instead of Content-Type
                'content-type' => 'application/x-www-form-urlencoded',
            ],
            'body' => 'foo=bar&bim=baz',
        ]);
        self::assertEquals('POST', $request->getMethod());
        self::assertEquals(['foo' => 'bar', 'bim' => 'baz'], $request->getParsedBody());
    }

    public function test POST JSON body is not parsed()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['foo' => 'bar']),
        ]);
        self::assertEquals('POST', $request->getMethod());
        self::assertEquals(null, $request->getParsedBody());
        self::assertEquals(['foo' => 'bar'], json_decode($request->getBody()->getContents(), true));
    }

    public function test multipart form data is not supported()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data',
            ],
            'body' => 'abcd',
        ]);
        self::assertEquals('POST', $request->getMethod());
        self::assertNull(null, $request->getParsedBody());
    }

    public function test cookies are supported()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'GET',
            'headers' => [
                'Cookie' => 'tz=Europe%2FParis; four=two+%2B+2; theme=light',
            ],
        ]);
        self::assertEquals([
            'tz' => 'Europe/Paris',
            'four' => 'two + 2',
            'theme' => 'light'
        ], $request->getCookieParams());
    }

    public function test arrays in query string are supported()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'GET',
            'queryStringParameters' => [
                'vars[val1]' => 'foo',
                'vars[val2][]' => 'bar',
            ],
        ]);

        self::assertEquals([
            'vars' => [
                'val1' => 'foo',
                'val2' => [
                    'bar',
                ]
            ]
        ], $request->getQueryParams());
    }
}
