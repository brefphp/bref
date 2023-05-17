<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\InvalidLambdaEvent;

class HttpRequestEventTest extends CommonHttpTest
{
    private HttpRequestEvent $event;

    protected function fromFixture(string $file): void
    {
        $this->event = new HttpRequestEvent(json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR));
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
        [$user] = $this->event->getBasicAuthCredentials();
        $this->assertEquals($expected, $user);
    }

    protected function assertBasicAuthPassword(string $expected): void
    {
        [$user, $pass] = $this->event->getBasicAuthCredentials();
        $this->assertEquals($expected, $pass);
    }

    public function test empty invocation will have friendly error message()
    {
        $message = "This handler expected to be invoked with a API Gateway or ALB event (check that you are using the correct Bref runtime: https://bref.sh/docs/runtimes/#bref-runtimes).\nInstead, the handler was invoked with invalid event data: null";

        $this->expectException(InvalidLambdaEvent::class);
        $this->expectExceptionMessage($message);

        new HttpRequestEvent(null);
    }

    /**
     * @dataProvider provide query strings
     */
    public function test query string to array(string $query, array $expectedOutput)
    {
        $reflection = new \ReflectionClass(HttpRequestEvent::class);
        $method = $reflection->getMethod('queryStringToArray');
        $method->setAccessible(true);
        $result = $method->invokeArgs($reflection->newInstanceWithoutConstructor(), [$query]);

        $this->assertEquals($expectedOutput, $result);
    }

    public function provide query strings(): iterable
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

    /**
     * @dataProvider provide query strings for event
     */
    public function test query string will be parsed correctly(array $expected, string $normalizedQs, string $queryString)
    {
        $event = new HttpRequestEvent([
            'httpMethod' => 'GET',
            'version' => '2.0',
            'rawQueryString' => $queryString,
        ]);

        self::assertSame($expected, $event->getQueryParameters());
        self::assertSame($normalizedQs, $event->getQueryString());
    }

    public function provide query strings for event(): array
    {
        return [
            [['foo' => 'bar'], 'foo=bar', 'foo=bar'],
            [['foo' => 'bar  '], 'foo=bar%20%20', '   foo=bar  '],
            [['?foo' => 'bar'], '%3Ffoo=bar', '?foo=bar'],
            [['#foo' => 'bar'], '%23foo=bar', '#foo=bar'],
            [['foo' => 'bar'], 'foo=bar', '&foo=bar'],
            [['foo' => 'bar', 'bar' => 'foo'], 'foo=bar&bar=foo', 'foo=bar&bar=foo'],
            [['foo' => 'bar', 'bar' => 'foo'], 'foo=bar&bar=foo', 'foo=bar&&bar=foo'],
            [['foo' => ['bar' => ['baz' => ['bax' => 'bar']]]], 'foo%5Bbar%5D%5Bbaz%5D%5Bbax%5D=bar', 'foo[bar][baz][bax]=bar'],
            [['foo' => ['bar' => 'bar']], 'foo%5Bbar%5D=bar', 'foo[bar] [baz]=bar'],
            [['foo' => ['bar' => ['baz' => ['bar', 'foo']]]], 'foo%5Bbar%5D%5Bbaz%5D%5B0%5D=bar&foo%5Bbar%5D%5Bbaz%5D%5B1%5D=foo', 'foo[bar][baz][]=bar&foo[bar][baz][]=foo'],
            [['foo' => ['bar' => [['bar'], ['foo']]]], 'foo%5Bbar%5D%5B0%5D%5B0%5D=bar&foo%5Bbar%5D%5B1%5D%5B0%5D=foo', 'foo[bar][][]=bar&foo[bar][][]=foo'],
            [['option' => ''], 'option=', 'option'],
            [['option' => '0'], 'option=0', 'option=0'],
            [['option' => '1'], 'option=1', 'option=1'],
            [['foo' => 'bar=bar=='], 'foo=bar%3Dbar%3D%3D', 'foo=bar=bar=='],
            [['options' => ['option' => '0']], 'options%5Boption%5D=0', 'options[option]=0'],
            [['options' => ['option' => 'foobar']], 'options%5Boption%5D=foobar', 'options[option]=foobar'],
            [['sum' => '10\\2=5'], 'sum=10%5C2%3D5', 'sum=10%5c2%3d5'],

            // Special cases
            [
                [
                    'a' => '<==  foo bar  ==>',
                    'b' => '###Hello World###',
                ],
                'a=%3C%3D%3D%20%20foo%20bar%20%20%3D%3D%3E&b=%23%23%23Hello%20World%23%23%23',
                'a=%3c%3d%3d%20%20foo%20bar%20%20%3d%3d%3e&b=%23%23%23Hello%20World%23%23%23',
            ],
            [
                [
                    'a' => '<==  foo bar  ==>',
                    'b' => '###Hello World###',
                ],
                'a=%3C%3D%3D%20%20foo%20bar%20%20%3D%3D%3E&b=%23%23%23Hello%20World%23%23%23',
                'a=%3c%3d%3d%20%20foo+bar++%3d%3d%3e&b=%23%23%23Hello+World%23%23%23',
            ],
            [
                ['str' => "A string with containing \0\0\0 nulls"],
                'str=A%20string%20with%20containing%20%00%00%00%20nulls',
                'str=A%20string%20with%20containing%20%00%00%00%20nulls',
            ],
            [
                [
                    'arr_1' => 'sid',
                    'arr' => ['4' => 'fred'],
                ],
                'arr_1=sid&arr%5B4%5D=fred',
                'arr[1=sid&arr[4][2=fred',
            ],
            [
                [
                    'arr_1' => 'sid',
                    'arr' => ['4' => ['[2' => 'fred']],
                ],
                'arr_1=sid&arr%5B4%5D%5B%5B2%5D=fred',
                'arr[1=sid&arr[4][[2][3[=fred',
            ],
        ];
    }
}
