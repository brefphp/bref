<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\InvalidLambdaEvent;

class HttpRequestEventTest extends CommonHttpTest
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

        // Also check that the cookies are available in the HTTP headers (they should be)
        $expectedHeader = array_map(function (string $value, string $key): string {
            return $key . '=' . urlencode($value);
        }, $expected, array_keys($expected));
        $this->assertEquals(implode('; ', $expectedHeader), $this->event->getHeaders()['cookie'][0] ?? '');
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

    protected function assertSourceIp(string $expected): void
    {
        $this->assertEquals($expected, $this->event->getSourceIp());
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

    protected function assertPathParameters(array $expected): void
    {
        $this->assertEquals($expected, $this->event->getPathParameters());
    }

    protected function assertBasicAuthUser(string $expected): void
    {
        [$user, $pass] = $this->event->getBasicAuthCredentials();
        $this->assertEquals($expected, $user);
    }

    protected function assertBasicAuthPassword(string $expected): void
    {
        [$user, $pass] = $this->event->getBasicAuthCredentials();
        $this->assertEquals($expected, $pass);
    }

    public function test empty invocation will have friendly error message()
    {
        $message = 'This handler expected to be invoked with a API Gateway or ALB event. Instead, the handler was invoked with invalid event data: null';

        $this->expectException(InvalidLambdaEvent::class);
        $this->expectExceptionMessage($message);

        new HttpRequestEvent(null);
    }

    /**
     * @dataProvider queryStringProvider
     */
    public function testQueryStringToArray(string $query, array $expectedOutput)
    {
        $reflection = new \ReflectionClass(HttpRequestEvent::class);
        $method = $reflection->getMethod('queryStringToArray');
        $method->setAccessible(true);
        $result = $method->invokeArgs($reflection->newInstanceWithoutConstructor(), [$query]);

        $this->assertEquals($expectedOutput, $result);
    }

    public function queryStringProvider(): iterable
    {
        yield ['', []];

        yield [
            'foo_bar=2',
            [
                'foo_bar' => '2',
            ],
        ];

        yield [
            'foo_bar=v1&foo.bar=v2',
            [
                'foo_bar' => 'v1',
                'foo.bar' => 'v2',
            ],
        ];

        yield [
            'foo_bar=v1&foo.bar=v2&foo.bar_extra=v3&foo_bar3=v4',
            [
                'foo_bar' => 'v1',
                'foo.bar' => 'v2',
                'foo.bar_extra' => 'v3',
                'foo_bar3' => 'v4',
            ],
        ];

        yield [
            'foo_bar.baz=v1',
            [
                'foo_bar.baz' => 'v1',
            ],
        ];

        yield [
            'foo_bar=v1&k[foo.bar]=v2',
            [
                'foo_bar' => 'v1',
                'k' => ['foo.bar' => 'v2'],
            ],
        ];

        yield [
            'k.1=v.1&k.2[s.k1]=v.2&k.2[s.k2]=v.3',
            [
                'k.1' => 'v.1',
                'k.2' => [
                    's.k1' => 'v.2',
                    's.k2' => 'v.3',
                ],
            ],
        ];

        yield [
            'foo.bar%5B0%5D=v1&foo.bar_extra%5B0%5D=v2&foo.bar.extra%5B0%5D=v3',
            [
                'foo.bar' => ['v1'],
                'foo.bar_extra' => ['v2'],
                'foo.bar.extra' => ['v3'],
            ],
        ];
    }
}
