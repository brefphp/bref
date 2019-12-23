<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpRequestEvent;

class HttpRequestEventTest extends AbstractHttpTest
{
    /** @var HttpRequestEvent */
    private $event;

    protected function fromFixture(string $file): void
    {
        $this->event = new HttpRequestEvent(json_decode(file_get_contents($file), true));
    }

    protected function assertBody(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getBody());
    }

    protected function assertContentType(?string $expected): void
    {
        $this->assertEquals($expected, $this->event->getContentType());
        if ($expected) {
            $this->assertHeader('content-type', [$expected]);
        }
    }

    protected function assertCookies(array $expected): void
    {
        $this->assertEquals($expected, $this->event->getCookies());
    }

    protected function assertHeaders(array $expected): void
    {
        $this->assertEquals($expected, $this->event->getHeaders());
    }

    protected function assertHeader(string $header, array $expectedValue): void
    {
        $this->assertArrayHasKey($header, $this->event->getHeaders());
        $this->assertEquals($expectedValue, $this->event->getHeaders()[$header]);
    }

    protected function assertMethod(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getMethod());
    }

    protected function assertPath(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getPath());
    }

    protected function assertQueryString(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getQueryString());
    }

    protected function assertQueryParameters(array $expected): void
    {
        $this->assertEquals($expected, $this->event->getQueryParameters());
    }

    protected function assertProtocol(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getProtocol());
    }

    protected function assertProtocolVersion(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getProtocolVersion());
    }

    protected function assertRemotePort(int $expected): void
    {
        $this->assertEquals($expected, $this->event->getRemotePort());
    }

    protected function assertServerName(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getServerName());
    }

    protected function assertServerPort(int $expected): void
    {
        $this->assertEquals($expected, $this->event->getServerPort());
    }

    protected function assertUri(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getUri());
    }

    protected function assertHasMultiHeader(bool $expected): void
    {
        $this->assertEquals($expected, $this->event->hasMultiHeader());
    }

    protected function assertParsedBody(array $expected): void
    {
        // Not applicable here since the class doesn't parse the body
    }

    protected function assertUploadedFile(
        string $key,
        string $filename,
        string $mimeType,
        int $error,
        int $size,
        string $content
    ): void {
        // Not applicable here since the class doesn't parse the body
    }
}
