<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpRequestEvent;
use Bref\Test\HttpRequestProxyTest;
use PHPUnit\Framework\TestCase;

class HttpRequestEventTest extends TestCase implements HttpRequestProxyTest
{
    public function test simple request()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-simple.json');

        $this->assertEquals('', $event->getBody());
        $this->assertNull($event->getContentType());
        $this->assertEquals([], $event->getCookies());
        $this->assertEquals([
            'accept' => ['*/*'],
            'accept-encoding' => ['gzip, deflate'],
            'cache-control' => ['no-cache'],
            'host' => ['example.org'],
            'user-agent' => ['PostmanRuntime/7.20.1'],
            'x-amzn-trace-id' => ['Root=1-ffffffff-ffffffffffffffffffffffff'],
            'x-forwarded-for' => ['1.1.1.1'],
            'x-forwarded-port' => ['443'],
            'x-forwarded-proto' => ['https'],
        ], $event->getHeaders());
        $this->assertEquals('GET', $event->getMethod());
        $this->assertEquals('/path', $event->getPath());
        $this->assertEquals('HTTP/1.1', $event->getProtocol());
        $this->assertEquals('1.1', $event->getProtocolVersion());
        $this->assertEquals([], $event->getQueryParameters());
        $this->assertEquals('', $event->getQueryString());
        $this->assertEquals(443, $event->getRemotePort());
        $this->assertEquals('example.org', $event->getServerName());
        $this->assertEquals(443, $event->getServerPort());
        $this->assertEquals('/path', $event->getUri());
        $this->assertFalse($event->hasMultiHeader());
    }

    public function test request with query string()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-query-string.json');

        $this->assertEquals('/path', $event->getPath());
        $this->assertEquals(['foo' => 'bar'], $event->getQueryParameters());
        $this->assertEquals('foo=bar', $event->getQueryString());
        $this->assertEquals('/path?foo=bar', $event->getUri());
    }

    public function test request with multivalues query string have basic support()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-query-string-multivalue.json');

        // TODO The feature is not implemented yet
        $this->assertEquals(['foo' => 'bar'], $event->getQueryParameters());
        $this->assertEquals('foo=bar', $event->getQueryString());
        $this->assertEquals('/path?foo=bar', $event->getUri());
    }

    public function test request with arrays in query string()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-query-string-arrays.json');

        $this->assertEquals([
            'vars' => [
                'val1' => 'foo',
                'val2' => ['bar'],
            ],
        ], $event->getQueryParameters());
        $this->assertEquals('vars%5Bval1%5D=foo&vars%5Bval2%5D%5B%5D=bar', $event->getQueryString());
        $this->assertEquals('/path?vars%5Bval1%5D=foo&vars%5Bval2%5D%5B%5D=bar', $event->getUri());
    }

    public function test request with custom header()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-header-custom.json');

        $this->assertArrayHasKey('x-my-header', $event->getHeaders());
        $this->assertEquals(['Hello world'], $event->getHeaders()['x-my-header']);
    }

    public function test request with custom multi header()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-header-custom-multivalue.json');

        $this->assertArrayHasKey('x-my-header', $event->getHeaders());
        $this->assertEquals(['Hello world', 'Hello john'], $event->getHeaders()['x-my-header']);
        $this->assertTrue($event->hasMultiHeader());
    }

    public function test POST request with raw body()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-body-json.json');

        $this->assertEquals('PUT', $event->getMethod());
        $this->assertEquals('application/json', $event->getContentType());
        $this->assertEquals([13], $event->getHeaders()['content-length']);
        $this->assertEquals('{"foo":"bar"}', $event->getBody());
    }

    public function test POST request with form data()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-body-form.json');

        $this->assertEquals('POST', $event->getMethod());
        $this->assertEquals('application/x-www-form-urlencoded', $event->getContentType());
        $this->assertEquals([15], $event->getHeaders()['content-length']);
        $this->assertEquals('foo=bar&bim=baz', $event->getBody());
    }

    public function provideHttpMethodsWithRequestBodySupport(): array
    {
        return [
            'POST' => [
                'method' => 'POST',
            ],
            'PUT' => [
                'method' => 'PUT',
            ],
            'PATCH' => [
                'method' => 'PATCH',
            ],
        ];
    }

    /**
     * @see https://github.com/brefphp/bref/issues/162
     *
     * @dataProvider provideHttpMethodsWithRequestBodySupport
     */
    public function test request with body and no content length(string $method)
    {
        // These requests do not have a Content-Length header on purpose
        $event = $this->fromJson(__DIR__ . "/Fixture/apigateway-missing-content-length-$method.json");

        $this->assertEquals($method, $event->getMethod());
        // We check the header is added automatically
        $this->assertEquals([13], $event->getHeaders()['content-length']);
    }

    public function test request supports utf8 characters in body()
    {
        // These requests have a multibyte body: 'Hello 🌍'
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-body-utf8.json');

        $this->assertEquals('Hello 🌍', $event->getBody());
        // We check the header is added automatically and takes multibyte into account
        $this->assertEquals([10], $event->getHeaders()['content-length']);
    }

    public function test the content type header is not case sensitive()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-content-type-lower-case.json');

        $this->assertEquals('application/json', $event->getContentType());
        $this->assertEquals([13], $event->getHeaders()['content-length']);
    }

    public function test POST request with multipart form data()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-body-form-multipart.json');

        $this->assertEquals('multipart/form-data; boundary=testBoundary', $event->getContentType());
        $this->assertEquals([152], $event->getHeaders()['content-length']);
        $body = "--testBoundary\r
Content-Disposition: form-data; name=\"foo\"\r
\r
bar\r
--testBoundary\r
Content-Disposition: form-data; name=\"bim\"\r
\r
baz\r
--testBoundary--\r
";
        $this->assertEquals($body, $event->getBody());
    }

    public function test POST request with multipart form data containing arrays()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-body-form-multipart-arrays.json');

        $this->assertEquals('multipart/form-data; boundary=testBoundary', $event->getContentType());
        $this->assertEquals([186], $event->getHeaders()['content-length']);
        $body = "--testBoundary\r
Content-Disposition: form-data; name=\"delete[categories][]\"\r
\r
123\r
--testBoundary\r
Content-Disposition: form-data; name=\"delete[categories][]\"\r
\r
456\r
--testBoundary--\r
";
        $this->assertEquals($body, $event->getBody());
    }

    public function test POST request with multipart file uploads()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-body-form-multipart-files.json');

        $this->assertEquals('multipart/form-data; boundary=testBoundary', $event->getContentType());
        $this->assertEquals([323], $event->getHeaders()['content-length']);
        $body = "--testBoundary\r
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
";
        $this->assertEquals($body, $event->getBody());
    }

    public function test request with cookies()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-cookies.json');

        $this->assertEquals([
            'tz' => 'Europe/Paris',
            'four' => 'two + 2',
            'theme' => 'light',
        ], $event->getCookies());
    }

    public function test POST request with base64 encoded body()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-body-base64.json');

        $this->assertEquals('POST', $event->getMethod());
        $this->assertEquals('application/x-www-form-urlencoded', $event->getContentType());
        $this->assertEquals([7], $event->getHeaders()['content-length']);
        $this->assertEquals('foo=bar', $event->getBody());
    }

    public function test PUT request()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-method-PUT.json');
        $this->assertEquals('PUT', $event->getMethod());
    }

    public function test PATCH request()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-method-PATCH.json');
        $this->assertEquals('PATCH', $event->getMethod());
    }

    public function test DELETE request()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-method-DELETE.json');
        $this->assertEquals('DELETE', $event->getMethod());
    }

    public function test OPTIONS request()
    {
        $event = $this->fromJson(__DIR__ . '/Fixture/apigateway-method-OPTIONS.json');
        $this->assertEquals('OPTIONS', $event->getMethod());
    }

    private function fromJson(string $file): HttpRequestEvent
    {
        return new HttpRequestEvent(json_decode(file_get_contents($file), true));
    }
}
