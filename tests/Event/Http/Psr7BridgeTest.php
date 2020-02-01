<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\Psr7Bridge;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\Response\JsonResponse;

class Psr7BridgeTest extends CommonHttpTest
{
    /** @var ServerRequestInterface */
    private $request;

    public function test I can create a response from a PSR7 response()
    {
        $psr7Response = new JsonResponse(['foo' => 'bar'], 404);

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
    }

    protected function assertProtocolVersion(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getProtocolVersion());
        $this->assertEquals($expected, $this->request->getServerParams()['SERVER_PROTOCOL']);
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
        /** @var UploadedFileInterface $uploadedFile */
        $uploadedFile = $uploadedFiles[$key];
        $this->assertEquals($filename, $uploadedFile->getClientFilename());
        $this->assertEquals($mimeType, $uploadedFile->getClientMediaType());
        $this->assertEquals($error, $uploadedFile->getError());
        $this->assertEquals($size, $uploadedFile->getSize());
        $this->assertEquals($content, $uploadedFile->getStream()->getContents());
    }
}
