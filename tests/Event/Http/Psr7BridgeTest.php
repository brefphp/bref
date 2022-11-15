<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\Psr7Bridge;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class Psr7BridgeTest extends CommonHttpTest
{
    /** @var ServerRequestInterface */
    private $request;

    public function test I can create a response from a PSR7 response()
    {
        $psr7Response = new Response(404, [
            'Content-Type' => 'application/json',
        ], json_encode(['foo' => 'bar']));

        $response = Psr7Bridge::convertResponse($psr7Response);
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 404,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['foo' => 'bar']),
        ], $response->toApiGatewayFormat());
    }

    public function test request with basic auth contains a user and password()
    {
        $this->fromFixture(__DIR__ . '/Fixture/ag-v1-header-basic-auth.json');

        $this->assertEquals('fake', $this->request->getServerParams()['PHP_AUTH_USER']);
        $this->assertEquals('secret', $this->request->getServerParams()['PHP_AUTH_PW']);
    }

    public function test multipart form content type can have a suffix()
    {
        $this->fromFixture(__DIR__ . '/Fixture/ag-v1-body-base64-utf8.json');

        $this->assertContentType('application/x-www-form-urlencoded;charset=UTF-8');
        $this->assertParsedBody([
            'foo' => 'bar',
        ]);
    }

    protected function fromFixture(string $file): void
    {
        $event = new HttpRequestEvent(json_decode(file_get_contents($file), true));
        $context = new Context('', 0, '', '');
        $this->request = Psr7Bridge::convertRequest($event, $context);
    }

    protected function assertBody(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getBody()->getContents());
    }

    protected function assertContentType(?string $expected): void
    {
        $this->assertEquals($expected, $this->request->getHeaderLine('Content-Type'));
    }

    protected function assertCookies(array $expected): void
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
        $this->assertEquals($expected, $this->request->getServerParams()['QUERY_STRING']);
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

    protected function assertParsedBody(array $expected): void
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
        \assert($uploadedFile instanceof UploadedFileInterface);
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
}
