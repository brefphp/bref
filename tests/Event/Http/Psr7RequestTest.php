<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\Psr7RequestFactory;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

class Psr7RequestTest extends AbstractHttpTest
{
    /** @var ServerRequestInterface */
    private $request;

    protected function fromFixture(string $file): void
    {
        $event = new HttpRequestEvent(json_decode(file_get_contents($file), true));
        $this->request = Psr7RequestFactory::fromEvent($event);
    }

    protected function assertBody(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getBody()->getContents());
    }

    protected function assertContentType($expected): void
    {
        $this->assertEquals($expected, $this->request->getHeaderLine('Content-Type'));
    }

    protected function assertCookies($expected): void
    {
        $this->assertEquals($expected, $this->request->getCookieParams());
    }

    protected function assertHeaders(array $expected): void
    {
        $this->assertEquals($expected, $this->request->getHeaders());
    }

    protected function assertMethod($expected): void
    {
        $this->assertEquals($expected, $this->request->getMethod());
        $this->assertEquals($expected, $this->request->getServerParams()['REQUEST_METHOD']);
    }

    protected function assertPath($expected): void
    {
        $this->assertEquals($expected, $this->request->getUri()->getPath());
    }

    protected function assertQueryString($expected): void
    {
        $this->assertEquals($expected, $this->request->getUri()->getQuery());
        $this->assertEquals($expected, $this->request->getServerParams()['QUERY_STRING']);
    }

    protected function assertQueryParameters($expected): void
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
