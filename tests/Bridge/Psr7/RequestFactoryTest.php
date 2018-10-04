<?php
declare(strict_types=1);

namespace Bref\Test\Bridge\Psr7;

use Bref\Bridge\Psr7\RequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

class RequestFactoryTest extends TestCase
{
    public function test create basic request()
    {
        $currentTimestamp = time();

        $request = RequestFactory::fromLambdaEvent([
            'path' => '/test',
            'httpMethod' => 'GET',
            'queryStringParameters' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            'requestContext' => [
                'protocol' => '1.1',
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

    public function test multipart form data is supported()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
            ],
            'body' =>
"--testBoundary\r
Content-Disposition: form-data; name=\"foo\"\r
\r
bar\r
--testBoundary\r
Content-Disposition: form-data; name=\"bim\"\r
\r
baz\r
--testBoundary--\r
",
        ]);
        self::assertEquals('POST', $request->getMethod());
        self::assertEquals(['foo' => 'bar', 'bim' => 'baz'], $request->getParsedBody());
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

    public function test arrays in name are supported with multipart form data()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
            ],
            'body' =>
"--testBoundary\r
Content-Disposition: form-data; name=\"delete[categories][]\"\r
\r
123\r
--testBoundary\r
Content-Disposition: form-data; name=\"delete[categories][]\"\r
\r
456\r
--testBoundary--\r
",
        ]);
        self::assertEquals('POST', $request->getMethod());
        self::assertEquals(
            [
            'delete' => [
                'categories' => [
                    '123',
                    '456',
                    ],
                ],
            ],
            $request->getParsedBody()
        );
    }

    public function test files are supported with multipart form data()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
            ],
            'body' =>
"--testBoundary\r
Content-Disposition: form-data; name=\"foo\"; filename=\"lorem.txt\"\r
Content-Type: text/plain\r
\r
Lorem ipsum dolor sit amet,
consectetur adipiscing elit.
\r
--testBoundary\r
Content-Disposition: form-data; name=\"bar\"; filename=\"cars.csv\"\r
\r
Year,Make,Model
1997,Ford,E350
2000,Mercury,Cougar
\r
--testBoundary--\r
",
        ]);
        self::assertEquals('POST', $request->getMethod());
        self::assertEquals([], $request->getParsedBody());
        self::assertEquals(
            [
                'foo',
                'bar',
            ],
            array_keys($request->getUploadedFiles())
        );

        /** @var UploadedFileInterface $foo */
        $foo = $request->getUploadedFiles()['foo'];
        self::assertInstanceOf(UploadedFileInterface::class, $foo);
        self::assertEquals('lorem.txt', $foo->getClientFilename());
        self::assertEquals('text/plain', $foo->getClientMediaType());
        self::assertEquals(UPLOAD_ERR_OK, $foo->getError());
        self::assertEquals(57, $foo->getSize());
        self::assertEquals(<<<RAW
Lorem ipsum dolor sit amet,
consectetur adipiscing elit.

RAW
            , $foo->getStream()->getContents());

        /** @var UploadedFileInterface $bar */
        $bar = $request->getUploadedFiles()['bar'];
        self::assertInstanceOf(UploadedFileInterface::class, $bar);
        self::assertEquals('cars.csv', $bar->getClientFilename());
        self::assertEquals('application/octet-stream', $bar->getClientMediaType()); // not set: fallback to application/octet-stream
        self::assertEquals(UPLOAD_ERR_OK, $bar->getError());
        self::assertEquals(51, $bar->getSize());
        self::assertEquals(<<<RAW
Year,Make,Model
1997,Ford,E350
2000,Mercury,Cougar

RAW
            , $bar->getStream()->getContents());
    }

    public function test POST base64 encoded body is supported()
    {
        $request = RequestFactory::fromLambdaEvent([
            'httpMethod' => 'POST',
            'isBase64Encoded' => true,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'body' => base64_encode('foo=bar'),
        ]);
        self::assertEquals('POST', $request->getMethod());
        self::assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }
}
