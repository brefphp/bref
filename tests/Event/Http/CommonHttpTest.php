<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Test\HttpRequestProxyTest;
use PHPUnit\Framework\TestCase;

abstract class CommonHttpTest extends TestCase implements HttpRequestProxyTest
{
    public function provide API Gateway versions(): array
    {
        return [
            'v1' => [1],
            'v2' => [2],
        ];
    }

    public function test request with no version fallbacks to v1()
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-no-version.json");

        $this->assertBody('');
        $this->assertContentType(null);
        $this->assertHeaders([
            'accept' => ['*/*'],
            'accept-encoding' => ['gzip, deflate'],
            'cache-control' => ['no-cache'],
            'host' => ['example.org'],
            'user-agent' => ['PostmanRuntime/7.20.1'],
            'x-amzn-trace-id' => ['Root=1-ffffffff-ffffffffffffffffffffffff'],
            'x-forwarded-for' => ['1.1.1.1'],
            'x-forwarded-port' => ['443'],
            'x-forwarded-proto' => ['https'],
        ]);
        $this->assertMethod('GET');
        $this->assertUri('/path');
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test simple request(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-simple.json");

        $this->assertBody('');
        $this->assertContentType(null);
        $this->assertCookies([]);
        $this->assertHeaders([
            'accept' => ['*/*'],
            'accept-encoding' => ['gzip, deflate'],
            'cache-control' => ['no-cache'],
            'host' => ['example.org'],
            'user-agent' => ['PostmanRuntime/7.20.1'],
            'x-amzn-trace-id' => ['Root=1-ffffffff-ffffffffffffffffffffffff'],
            'x-forwarded-for' => ['1.1.1.1'],
            'x-forwarded-port' => ['443'],
            'x-forwarded-proto' => ['https'],
        ]);
        $this->assertMethod('GET');
        $this->assertPath('/path');
        $this->assertProtocol('HTTP/1.1');
        $this->assertProtocolVersion('1.1');
        $this->assertQueryParameters([]);
        $this->assertQueryString('');
        $this->assertRemotePort(443);
        $this->assertServerName('example.org');
        $this->assertServerPort(443);
        $this->assertUri('/path');
        $this->assertHasMultiHeader(false);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with query string(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-query-string.json");

        $this->assertPath('/path');
        $this->assertQueryParameters(['foo' => 'bar']);
        $this->assertQueryString('foo=bar');
        $this->assertUri('/path?foo=bar');
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with multivalues query string have basic support(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-query-string-multivalue.json");

        // TODO The feature is not implemented yet
        $this->assertQueryParameters(['foo' => 'bar']);
        $this->assertQueryString('foo=bar');
        $this->assertUri('/path?foo=bar');
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with arrays in query string(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-query-string-arrays.json");

        $this->assertQueryParameters([
            'vars' => [
                'val1' => 'foo',
                'val2' => ['bar'],
            ],
        ]);
        if ($version === 2) {
            // Numeric keys are added as an artifact of us parsing the query string
            // Both format are valid and semantically identical
            $this->assertQueryString('vars%5Bval1%5D=foo&vars%5Bval2%5D%5B0%5D=bar');
            $this->assertUri('/path?vars%5Bval1%5D=foo&vars%5Bval2%5D%5B0%5D=bar');
        } else {
            $this->assertQueryString('vars%5Bval1%5D=foo&vars%5Bval2%5D%5B%5D=bar');
            $this->assertUri('/path?vars%5Bval1%5D=foo&vars%5Bval2%5D%5B%5D=bar');
        }
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with custom header(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-header-custom.json");
        $this->assertHeader('x-my-header', ['Hello world']);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with custom multi header(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-header-custom-multivalue.json");
        if ($version === 2) {
            // In v2, multi-value headers are joined by a comma
            // See https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html
            $this->assertHeader('x-my-header', ['Hello world,Hello john']);
        } else {
            $this->assertHeader('x-my-header', ['Hello world', 'Hello john']);
            $this->assertHasMultiHeader(true);
        }
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with raw body(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-body-json.json");

        $this->assertMethod('PUT');
        $this->assertContentType('application/json');
        $this->assertHeader('content-length', [13]);
        $this->assertBody('{"foo":"bar"}');
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with form data(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-body-form.json");

        $this->assertMethod('POST');
        $this->assertContentType('application/x-www-form-urlencoded');
        $this->assertHeader('content-length', [15]);
        $this->assertBody('foo=bar&bim=baz');
        $this->assertParsedBody([
            'foo' => 'bar',
            'bim' => 'baz',
        ]);
    }

    public function provideHttpMethodsWithRequestBodySupport(): array
    {
        return [
            'POST v1' => [
                'version' => 1,
                'method' => 'POST',
            ],
            'POST v2' => [
                'version' => 2,
                'method' => 'POST',
            ],
            'PUT v1' => [
                'version' => 1,
                'method' => 'PUT',
            ],
            'PUT v2' => [
                'version' => 2,
                'method' => 'PUT',
            ],
            'PATCH v1' => [
                'version' => 1,
                'method' => 'PATCH',
            ],
            'PATCH v2' => [
                'version' => 2,
                'method' => 'PATCH',
            ],
        ];
    }

    /**
     * @see https://github.com/brefphp/bref/issues/162
     *
     * @dataProvider provideHttpMethodsWithRequestBodySupport
     */
    public function test request with body and no content length(int $version, string $method)
    {
        // These requests do not have a Content-Length header on purpose
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-missing-content-length-$method.json");

        $this->assertMethod($method);
        // We check the header is added automatically
        $this->assertHeader('content-length', [13]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request supports utf8 characters in body(int $version)
    {
        // These requests have a multibyte body: 'Hello 🌍'
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-body-utf8.json");

        $this->assertBody('Hello 🌍');
        // We check the header is added automatically and takes multibyte into account
        $this->assertHeader('content-length', [10]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test the content type header is not case sensitive(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-content-type-lower-case.json");

        $this->assertContentType('application/json');
        $this->assertHeader('content-length', [13]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with multipart form data(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-body-form-multipart.json");

        $this->assertContentType('multipart/form-data; boundary=testBoundary');
        $this->assertHeader('content-length', [152]);
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
        $this->assertBody($body);
        $this->assertParsedBody([
            'foo' => 'bar',
            'bim' => 'baz',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with multipart form data containing arrays(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-body-form-multipart-arrays.json");

        $this->assertContentType('multipart/form-data; boundary=testBoundary');
        $this->assertHeader('content-length', [186]);
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
        $this->assertBody($body);
        $this->assertParsedBody([
            'delete' => [
                'categories' => [
                    '123',
                    '456',
                ],
            ],
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with multipart file uploads(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-body-form-multipart-files.json");

        $this->assertContentType('multipart/form-data; boundary=testBoundary');
        $this->assertHeader('content-length', [323]);
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
        $this->assertBody($body);
        $this->assertParsedBody([]);
        $this->assertUploadedFile(
            'foo',
            'lorem.txt',
            'text/plain',
            0,
            57,
            "Lorem ipsum dolor sit amet,\nconsectetur adipiscing elit.\n"
        );
        $this->assertUploadedFile(
            'bar',
            'cars.csv',
            'application/octet-stream',
            0,
            51,
            "Year,Make,Model\n1997,Ford,E350\n2000,Mercury,Cougar\n"
        );
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with cookies(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-cookies.json");

        $this->assertCookies([
            'tz' => 'Europe/Paris',
            'four' => 'two + 2',
            'theme' => 'light',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with base64 encoded body(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-body-base64.json");

        $this->assertMethod('POST');
        $this->assertContentType('application/x-www-form-urlencoded');
        $this->assertHeader('content-length', [7]);
        $this->assertBody('foo=bar');
        $this->assertParsedBody([
            'foo' => 'bar',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test PUT request(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-method-PUT.json");
        $this->assertMethod('PUT');
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test PATCH request(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-method-PATCH.json");
        $this->assertMethod('PATCH');
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test DELETE request(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-method-DELETE.json");
        $this->assertMethod('DELETE');
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test OPTIONS request(int $version)
    {
        $this->fromFixture(__DIR__ . "/Fixture/ag-v$version-method-OPTIONS.json");
        $this->assertMethod('OPTIONS');
    }

    abstract protected function fromFixture(string $file): void;

    abstract protected function assertBody(string $expected): void;

    abstract protected function assertContentType(?string $expected): void;

    abstract protected function assertCookies(array $expected): void;

    abstract protected function assertHeaders(array $expected): void;

    abstract protected function assertHeader(string $header, array $expectedValue): void;

    abstract protected function assertMethod(string $expected): void;

    abstract protected function assertPath(string $expected): void;

    abstract protected function assertQueryString(string $expected): void;

    abstract protected function assertQueryParameters(array $expected): void;

    abstract protected function assertProtocol(string $expected): void;

    abstract protected function assertProtocolVersion(string $expected): void;

    abstract protected function assertRemotePort(int $expected): void;

    abstract protected function assertServerName(string $expected): void;

    abstract protected function assertServerPort(int $expected): void;

    abstract protected function assertUri(string $expected): void;

    abstract protected function assertHasMultiHeader(bool $expected): void;

    abstract protected function assertParsedBody(array $expected): void;

    abstract protected function assertUploadedFile(
        string $key,
        string $filename,
        string $mimeType,
        int $error,
        int $size,
        string $content
    ): void;
}
