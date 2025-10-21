<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\Psr7Bridge;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

use function assert;

class Psr7BridgeTest extends CommonHttpTest
{
    private ServerRequestInterface $request;

    public function test I can create a response from a PSR7 response()
    {
        $psr7Response = new Response(404, [
            'Content-Type' => 'application/json',
        ], json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR));

        $response = Psr7Bridge::convertResponse($psr7Response);
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 404,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR),
        ], $response->toApiGatewayFormat());
    }

    public function test I can convert a request from a PSR7 response form data type()
    {
        $event = new HttpRequestEvent(json_decode(file_get_contents("/Fixture/ag-v$version-body-form-multipart-structured-arrays.json"), true, 512, JSON_THROW_ON_ERROR));

        $psr7Response = new Response(200, [
            'Content-Type' => 'multipart/form-data; boundary=testBoundary',
        ], "--testBoundary\r\nContent-Disposition: form-data; name=\"content\"\r\n\r\n<h1>Test content</h1>\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"some_id\"\r\n\r\n3034\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[0][other_id]\"\r\n\r\n4390954279\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[0][url]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[1][other_id]\"\r\n\r\n4313323164\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[1][url]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[2][other_id]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[2][url]\"\r\n\r\nhttps://someurl.com/node/745911\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"tags[0]\"\r\n\r\npublic health\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"tags[1]\"\r\n\r\npublic finance\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"_method\"\r\n\r\nPATCH\r\n--testBoundary--\r\n");

        $response = Psr7Bridge::convertRequest($psr7Response);
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
            ],
            'body' => [
                'content' => '<h1>Test content</h1>',
                'some_id' => '3034',
                'references' => [
                    [
                        'other_id' => '4390954279',
                        'url' => '',
                    ],
                    [
                        'other_id' => '4313323164',
                        'url' => '',
                    ],
                    [
                        'other_id' => '',
                        'url' => 'https://someurl.com/node/745911',
                    ],
                ],
                'tags' => [
                    'public health',
                    'public finance',
                ],
                '_method' => 'PATCH',
            ],
        ], $response->toApiGatewayFormat());
    }

    protected function fromFixture(string $file): void
    {
        $event = new HttpRequestEvent(json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR));
        $this->request = Psr7Bridge::convertRequest($event, Context::fake());
    }

    protected function assertBody(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getBody()->getContents());
    }

    protected function assertContentType(?string $expected): void
    {
        $this->assertEquals($expected, $this->request->getHeaderLine('Content-Type'));
    }

    protected function assertCookies(array $expected, string |null $expectedHeader = null): void
    {
        $this->assertEquals($expected, $this->request->getCookieParams());
    }

    protected function assertHeaders(array $expected): void
    {
        $this->assertEquals($expected, $this->request->getHeaders());
    }

    protected function assertMethod(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getMethod());
        $this->assertEquals($expected, $this->request->getServerParams()['REQUEST_METHOD']);
    }

    protected function assertPath(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getUri()->getPath());
    }

    protected function assertQueryString(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getUri()->getQuery());
        $this->assertEquals($expected, $this->request->getServerParams()['QUERY_STRING'] ?? '');
    }

    protected function assertQueryParameters(array $expected): void
    {
        $this->assertEquals($expected, $this->request->getQueryParams());
    }

    protected function assertProtocol(string $expected): void
    {
        $this->assertEquals($expected, 'HTTP/' . $this->request->getProtocolVersion());
        $this->assertEquals($expected, $this->request->getServerParams()['SERVER_PROTOCOL']);
    }

    protected function assertProtocolVersion(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getProtocolVersion());
    }

    protected function assertHeader(string $header, array $expectedValue): void
    {
        $this->assertTrue($this->request->hasHeader($header));
        $this->assertEquals($expectedValue, $this->request->getHeader($header));
    }

    protected function assertRemotePort(int $expected): void
    {
        // Nothing to do
    }

    protected function assertServerName(string $expected): void
    {
        // Nothing to do
    }

    protected function assertServerPort(int $expected): void
    {
        // Nothing to do
    }

    protected function assertUri(string $expected): void
    {
        $this->assertEquals($expected, (string) $this->request->getUri());
        $this->assertEquals($expected, $this->request->getServerParams()['REQUEST_URI']);
    }

    protected function assertHasMultiHeader(bool $expected): void
    {
        // Not applicable here
    }

    protected function assertParsedBody(array | null $expected): void
    {
        $this->assertEquals($expected, $this->request->getParsedBody());
    }

    protected function assertUploadedFile(
        string $key,
        string $filename,
        string $mimeType,
        int $error,
        int $size,
        string $content
    ): void {
        $uploadedFiles = $this->request->getUploadedFiles();
        $uploadedFile = $uploadedFiles[$key];
        assert($uploadedFile instanceof UploadedFileInterface);
        $this->assertEquals($filename, $uploadedFile->getClientFilename());
        $this->assertEquals($mimeType, $uploadedFile->getClientMediaType());
        $this->assertEquals($error, $uploadedFile->getError());
        $this->assertEquals($size, $uploadedFile->getSize());
        $this->assertEquals($content, $uploadedFile->getStream()->getContents());
    }

    protected function assertPathParameters(array $expected): void
    {
        $parameters = $this->request->getAttributes();
        unset($parameters['lambda-event'], $parameters['lambda-context']);
        $this->assertEquals($expected, $parameters);
    }

    protected function assertSourceIp(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getServerParams()['REMOTE_ADDR']);
    }

    protected function assertBasicAuthUser(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getServerParams()['PHP_AUTH_USER']);
    }

    protected function assertBasicAuthPassword(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getServerParams()['PHP_AUTH_PW']);
    }
}
