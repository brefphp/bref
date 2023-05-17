<?php declare(strict_types=1);

namespace Bref\Test\FpmRuntime;

use Bref\Context\Context;
use Bref\FpmRuntime\FastCgi\Timeout;
use Bref\FpmRuntime\FpmHandler;
use Bref\Test\HttpRequestProxyTest;
use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use PHPUnit\Framework\TestCase;

class FpmHandlerTest extends TestCase implements HttpRequestProxyTest
{
    use ArraySubsetAsserts;

    private ?FpmHandler $fpm = null;
    private Context $fakeContext;

    public function setUp(): void
    {
        parent::setUp();

        ob_start();
        $this->fakeContext = new Context('abc', time(), 'abc', 'abc');
    }

    public function tearDown(): void
    {
        $this->fpm->stop();
        ob_end_clean();
    }

    public function provide API Gateway versions(): array
    {
        return [
            'v1' => [1],
            'v2' => [2],
        ];
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test simple request(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            'requestContext' => [
                'protocol' => 'HTTP/1.1',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '{"protocol":"HTTP\/1.1"}',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with query string(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            'queryStringParameters' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello?foo=bar&bim=baz',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'foo=bar&bim=baz',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with multivalues query string have basic support(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            // See https://aws.amazon.com/blogs/compute/support-for-multi-value-parameters-in-amazon-api-gateway/
            'multiValueQueryStringParameters' => [
                'foo[]' => ['bar', 'baz'],
                'cards[]' => ['birthday'],
                'colors[][]' => ['red', 'blue'],
                'shapes[a][]' => ['square', 'triangle'],
                'myvar' => ['abc'],
            ],
            'queryStringParameters' => [
                'foo[]' => 'baz', // the 2nd value is preserved only by API Gateway
                'cards[]' => 'birthday',
                'colors[][]' => 'red',
                'shapes[a][]' => 'square',
                'myvar' => 'abc',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [
                'foo' => ['bar', 'baz'],
                'cards' => ['birthday'],
                'colors' => [['red'], ['blue']],
                'shapes' => ['a' => ['square', 'triangle']],
                'myvar' => 'abc',
            ],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'foo' => ['bar', 'baz'],
                'cards' => ['birthday'],
                'colors' => [['red'], ['blue']],
                'shapes' => ['a' => ['square', 'triangle']],
                'myvar' => 'abc',
            ],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello?foo%5B0%5D=bar&foo%5B1%5D=baz&cards%5B0%5D=birthday&colors%5B0%5D%5B0%5D=red&colors%5B1%5D%5B0%5D=blue&shapes%5Ba%5D%5B0%5D=square&shapes%5Ba%5D%5B1%5D=triangle&myvar=abc',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'foo%5B0%5D=bar&foo%5B1%5D=baz&cards%5B0%5D=birthday&colors%5B0%5D%5B0%5D=red&colors%5B1%5D%5B0%5D=blue&shapes%5Ba%5D%5B0%5D=square&shapes%5Ba%5D%5B1%5D=triangle&myvar=abc',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with requestContext array support()
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            'requestContext' => [
                'foo' => 'baz',
                'baz' => 'far',
                'data' => [
                    'recurse1' => 1,
                    'recurse2' => 2,
                ],
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '{"foo":"baz","baz":"far","data":{"recurse1":1,"recurse2":2}}',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with arrays in query string(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'queryStringParameters' => [
                'vars[val1]' => 'foo',
                'vars[val2][]' => 'bar',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [
                'vars' => [
                    'val1' => 'foo',
                    'val2' => ['bar'],
                ],
            ],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'vars' => [
                    'val1' => 'foo',
                    'val2' => ['bar'],
                ],
            ],
            '$_SERVER' => [
                'REQUEST_URI' => '/?vars%5Bval1%5D=foo&vars%5Bval2%5D%5B%5D=bar',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'vars%5Bval1%5D=foo&vars%5Bval2%5D%5B%5D=bar',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with custom header(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'path' => '/',
            'headers' => [
                'X-My-Header' => 'Hello world',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'HTTP_X_MY_HEADER' => 'Hello world',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with custom multi header(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'path' => '/',
            'headers' => [
                'X-My-Header' => 'Hello world',
            ],
            'multiValueHeaders' => [
                'X-My-Header' => ['Hello world'],
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'HTTP_X_MY_HEADER' => 'Hello world',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with raw body(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'application/json',
                'Content-Length' => mb_strlen(json_encode('Hello world!')),
            ],
            'body' => json_encode('Hello world!'),
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '14',
                'CONTENT_TYPE' => 'application/json',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_LENGTH' => '14',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '"Hello world!"',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with form data(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'body' => 'foo=bar&bim=baz',
            'headers' => [
                'Content-Length' => mb_strlen('foo=bar&bim=baz'),
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '15',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_CONTENT_LENGTH' => '15',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => 'foo=bar&bim=baz',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with form data and content type(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'body' => 'foo=bar&bim=baz',
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '15',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded;charset=UTF-8',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded;charset=UTF-8',
                'HTTP_CONTENT_LENGTH' => '15',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => 'foo=bar&bim=baz',
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
        $event = [
            'version' => '1.0',
            'httpMethod' => $method,
            'headers' => [
                'Content-Type' => 'application/json',
                // The Content-Length header is purposefully omitted
            ],
            'body' => json_encode('Hello world!'),
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '14',
                'CONTENT_TYPE' => 'application/json',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => $method,
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_LENGTH' => '14',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '"Hello world!"',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request supports utf8 characters in body(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'text/plain; charset=UTF-8',
                // The Content-Length header is purposefully omitted
            ],
            // Use a multibyte string
            'body' => 'Hello 🌍',
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '10',
                'CONTENT_TYPE' => 'text/plain; charset=UTF-8',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'text/plain; charset=UTF-8',
                'HTTP_CONTENT_LENGTH' => '10',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => 'Hello 🌍',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test the content type header is not case sensitive(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'headers' => [
                // content-type instead of Content-Type
                'content-type' => 'application/json',
                'content-length' => mb_strlen('{}'),
            ],
            'body' => '{}',
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '2',
                'CONTENT_TYPE' => 'application/json',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_LENGTH' => '2',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '{}',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with multipart form data(int $version)
    {
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
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
                'Content-Length' => mb_strlen($body),
            ],
            'body' => $body,
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'foo' => 'bar',
                'bim' => 'baz',
            ],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '152',
                'CONTENT_TYPE' => 'multipart/form-data; boundary=testBoundary',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'multipart/form-data; boundary=testBoundary',
                'HTTP_CONTENT_LENGTH' => '152',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with multipart form data containing arrays(int $version)
    {
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
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
                'Content-Length' => mb_strlen($body),
            ],
            'body' => $body,
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [
                'delete' => [
                    'categories' => [
                        '123',
                        '456',
                    ],
                ],
            ],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'delete' => [
                    'categories' => [
                        '123',
                        '456',
                    ],
                ],
            ],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '186',
                'CONTENT_TYPE' => 'multipart/form-data; boundary=testBoundary',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'multipart/form-data; boundary=testBoundary',
                'HTTP_CONTENT_LENGTH' => '186',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with cookies(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'headers' => [
                'Cookie' => 'tz=Europe%2FParis; four=two; theme=light',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [
                'tz' => 'Europe/Paris',
                'four' => 'two',
                'theme' => 'light',
            ],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'HTTP_COOKIE' => 'tz=Europe%2FParis; four=two; theme=light',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with multipart file uploads(int $version)
    {
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
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
                'Content-Length' => mb_strlen($body),
            ],
            'body' => $body,
        ];

        $expectedGlobalVariables = [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [
                'foo' => [
                    'name' => 'lorem.txt',
                    'type' => 'text/plain',
                    'error' => 0,
                    'size' => 57,
                    'content' => "Lorem ipsum dolor sit amet,\nconsectetur adipiscing elit.\n",
                    'full_path' => 'lorem.txt',
                ],
                'bar' => [
                    'name' => 'cars.csv',
                    'type' => '',
                    'error' => 0,
                    'size' => 51,
                    'content' => "Year,Make,Model\n1997,Ford,E350\n2000,Mercury,Cougar\n",
                    'full_path' => 'cars.csv',
                ],
            ],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '323',
                'CONTENT_TYPE' => 'multipart/form-data; boundary=testBoundary',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'multipart/form-data; boundary=testBoundary',
                'HTTP_CONTENT_LENGTH' => '323',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ];

        if (\PHP_VERSION_ID < 80100) {
            // full_path was introduced in PHP 8.1, remove it for lower versions
            unset($expectedGlobalVariables['$_FILES']['foo']['full_path']);
            unset($expectedGlobalVariables['$_FILES']['bar']['full_path']);
        }

        $this->assertGlobalVariables($event, $expectedGlobalVariables);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test POST request with base64 encoded body(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'POST',
            'isBase64Encoded' => true,
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Content-Length' => mb_strlen('foo=bar'),
            ],
            'body' => base64_encode('foo=bar'),
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [
                'foo' => 'bar',
            ],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'foo' => 'bar',
            ],
            '$_SERVER' => [
                'CONTENT_LENGTH' => '7',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'HTTP_CONTENT_LENGTH' => '7',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => 'foo=bar',
        ]);
    }

    public function test HTTP_HOST header()
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'headers' => [
                'Host' => 'www.example.com',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'SERVER_NAME' => 'www.example.com',
                'HTTP_HOST' => 'www.example.com',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test PUT request(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'PUT',
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'PUT',
                'QUERY_STRING' => '',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test PATCH request(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'PATCH',
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'PATCH',
                'QUERY_STRING' => '',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test DELETE request(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'DELETE',
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'DELETE',
                'QUERY_STRING' => '',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test OPTIONS request(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'OPTIONS',
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'OPTIONS',
                'QUERY_STRING' => '',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provide API Gateway versions
     */
    public function test request with basic auth(int $version)
    {
        $event = [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'headers' => [
                'Authorization' => 'Basic ZmFrZTpzZWNyZXQ=',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
                'HTTP_AUTHORIZATION' => 'Basic ZmFrZTpzZWNyZXQ=',
                // PHP-FPM automatically adds these variables
                'PHP_AUTH_USER' => 'fake',
                'PHP_AUTH_PW' => 'secret',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    /**
     * @dataProvider provideStatusCodes
     */
    public function test response with status code(int $expectedStatusCode)
    {
        $statusCode = $this->get('status-code.php', [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'queryStringParameters' => [
                'code' => $expectedStatusCode,
            ],
        ])['statusCode'];

        self::assertEquals($expectedStatusCode, $statusCode);
    }

    public function provideStatusCodes(): array
    {
        return [[200], [301], [302], [400], [401], [403], [404], [500], [504]];
    }

    public function test response with headers()
    {
        $response = $this->get('response-headers.php', [
            'version' => '1.0',
            'httpMethod' => 'GET',
        ]);

        self::assertStringStartsWith('PHP/', $response['headers']['X-Powered-By'] ?? '');
        unset($response['headers']['X-Powered-By']);
        self::assertEquals([
            'Content-Type' => 'application/json',
            'X-Multivalue' => 'bar',
        ], $response['headers']);
    }

    public function test response with multivalue headers()
    {
        $response = $this->get('response-headers.php', [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'multiValueHeaders' => [],
        ]);

        self::assertStringStartsWith('PHP/', $response['multiValueHeaders']['X-Powered-By'][0] ?? '');
        unset($response['multiValueHeaders']['X-Powered-By']);
        self::assertEquals([
            'Content-Type' => ['application/json'],
            'X-Multivalue' => ['foo', 'bar'],
        ], $response['multiValueHeaders']);
    }

    public function test response with cookies()
    {
        $cookieHeader = $this->get('cookies.php')['headers']['Set-Cookie'];

        // Dependending on the PHP version the date formatting is slightly different
        $cookieHeader = str_replace('12 Jan 2018', '12-Jan-2018', $cookieHeader);

        self::assertEquals('MyCookie=MyValue; expires=Fri, 12-Jan-2018 08:32:03 GMT; Max-Age=0; path=/hello/; domain=example.com; secure; HttpOnly', $cookieHeader);
    }

    public function test response with multiple cookies with multiheader()
    {
        $cookieHeader = $this->get('cookies.php', [
            'version' => '1.0',
            'httpMethod' => 'GET',
            'multiValueHeaders' => [],
        ])['multiValueHeaders']['Set-Cookie'];

        // Dependending on the PHP version the date formatting is slightly different
        $cookieHeader[0] = str_replace('12 Jan 2018', '12-Jan-2018', $cookieHeader[0]);
        $cookieHeader[1] = str_replace('12 Jan 2018', '12-Jan-2018', $cookieHeader[1]);

        self::assertEquals('MyCookie=FirstValue; expires=Fri, 12-Jan-2018 08:32:03 GMT; Max-Age=0; path=/hello/; domain=example.com; secure; HttpOnly', $cookieHeader[0]);
        self::assertEquals('MyCookie=MyValue; expires=Fri, 12-Jan-2018 08:32:03 GMT; Max-Age=0; path=/hello/; domain=example.com; secure; HttpOnly', $cookieHeader[1]);
    }

    public function test response with error_log()
    {
        $response = $this->get('error.php');

        self::assertStringStartsWith('PHP/', $response['headers']['X-Powered-By'] ?? '');
        unset($response['headers']['X-Powered-By']);
        self::assertEquals([
            'Content-Type' => 'text/html; charset=UTF-8',
        ], $response['headers']);
    }

    /**
     * Checks that a timeout cause by the PHP-FPM limit (not the Lambda limit) can be recovered from
     */
    public function test FPM timeouts are recovered from()
    {
        $this->fpm = new FpmHandler(__DIR__ . '/fixtures/timeout.php', __DIR__ . '/fixtures/php-fpm.conf');
        $this->fpm->start();

        try {
            $this->fpm->handle([
                'version' => '1.0',
                'httpMethod' => 'GET',
            ], $this->fakeContext);
            $this->fail('No exception was thrown');
        } catch (Timeout $e) {
            // PHP-FPM should work after that
            $statusCode = $this->fpm->handle([
                'version' => '1.0',
                'httpMethod' => 'GET',
                'queryStringParameters' => [
                    'timeout' => 0,
                ],
            ], $this->fakeContext)['statusCode'];
            self::assertEquals(200, $statusCode);
        }
    }

    /**
     * See https://github.com/brefphp/bref/issues/862
     */
    public function test worker logs are still written in case of a timeout()
    {
        $this->fpm = new FpmHandler(__DIR__ . '/fixtures/timeout.php', __DIR__ . '/fixtures/php-fpm.conf');
        $this->fpm->start();

        try {
            $this->fpm->handle([
                'version' => '1.0',
                'httpMethod' => 'GET',
            ], new Context('abc', time(), 'abc', 'abc'));
            $this->fail('No exception was thrown');
        } catch (Timeout $e) {
            $logs = ob_get_contents();
            self::assertStringContainsString('This is a log message', $logs);
        }
    }

    /**
     * @see https://github.com/brefphp/bref/issues/316
     */
    public function test large response()
    {
        // Repeat the test 5 times because some errors are random
        for ($i = 0; $i < 5; $i++) {
            $response = $this->get('large-response.php');

            self::assertStringEqualsFile(__DIR__ . '/fixtures/big-json.json', $response['body']);
        }
    }

    public function test warmer events do not invoke the application()
    {
        // Run `timeout.php` to make sure that the handler is not really executed.
        // If it was, then PHP-FPM would timeout (and error).
        $this->fpm = new FpmHandler(__DIR__ . '/fixtures/timeout.php', __DIR__ . '/fixtures/php-fpm.conf');
        $this->fpm->start();

        $result = $this->fpm->handle([
            'warmer' => true,
        ], $this->fakeContext);
        self::assertEquals(['Lambda is warm'], $result);
    }

    private function assertGlobalVariables(array $event, array $expectedGlobalVariables): void
    {
        $this->startFpm(__DIR__ . '/fixtures/request.php');
        $response = $this->fpm->handle($event, $this->fakeContext);

        $response = json_decode($response['body'], true);

        // Test global variables that cannot be hardcoded
        self::assertNotEmpty($response['$_SERVER']['HOME']);
        unset($response['$_SERVER']['HOME']);
        self::assertEqualsWithDelta(microtime(true), $response['$_SERVER']['REQUEST_TIME_FLOAT'], 5, '');
        unset($response['$_SERVER']['REQUEST_TIME_FLOAT']);
        self::assertEqualsWithDelta(time(), $response['$_SERVER']['REQUEST_TIME'], 5, '');
        unset($response['$_SERVER']['REQUEST_TIME']);

        // Test global variables that never change (simplifies all the tests)
        $response = $this->assertCommonServerVariables($response, $expectedGlobalVariables);

        self::assertEquals($expectedGlobalVariables, $response);
    }

    private function assertCommonServerVariables(array $response, array $expectedGlobalVariables): array
    {
        $expectedCommonVariables = [
            'USER' => get_current_user(),
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'SERVER_NAME' => 'localhost',
            'SERVER_ADDR' => '127.0.0.1',
            'SERVER_PORT' => '80',
            'REMOTE_PORT' => '80',
            'REMOTE_ADDR' => '127.0.0.1',
            'SERVER_SOFTWARE' => 'bref',
            'SCRIPT_FILENAME' => __DIR__ . '/fixtures/request.php',
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'FCGI_ROLE' => 'RESPONDER',
        ];

        // Allow to override some keys
        $overriddenKeys = array_intersect_key($expectedGlobalVariables['$_SERVER'], $expectedCommonVariables);
        $expectedCommonVariables = array_merge($expectedCommonVariables, $overriddenKeys);
        self::assertArraySubset($expectedCommonVariables, $response['$_SERVER']);

        $keysToRemove = array_keys(array_diff_key($expectedCommonVariables, $overriddenKeys));
        foreach ($keysToRemove as $keyToRemove) {
            unset($response['$_SERVER'][$keyToRemove]);
        }

        return $response;
    }

    private function get(string $file, ?array $event = null): array
    {
        $this->startFpm(__DIR__ . '/fixtures/' . $file);

        return $this->fpm->handle($event ?? [
            'version' => '1.0',
            'httpMethod' => 'GET',
        ], $this->fakeContext);
    }

    private function startFpm(string $handler): void
    {
        if ($this->fpm) {
            $this->fpm->stop();
        }
        $this->fpm = new FpmHandler($handler, __DIR__ . '/fixtures/php-fpm.conf');
        $this->fpm->start();
    }

    public function test request with encoded data()
    {
        $event = [
            'version' => '2.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            'rawPath' => '/hello',
            'rawQueryString' => 'a=%3c%3d%3d%20%20foo+bar++%3d%3d%3e&b=%23%23%23Hello+World%23%23%23',
            'queryStringParameters' => [
                'a' => '<==  foo bar  ==>',
                'b' => '###Hello World###',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [
                'a' => '<==  foo bar  ==>',
                'b' => '###Hello World###',
            ],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'a' => '<==  foo bar  ==>',
                'b' => '###Hello World###',
            ],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello?a=%3C%3D%3D%20%20foo%20bar%20%20%3D%3D%3E&b=%23%23%23Hello%20World%23%23%23',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'a=%3C%3D%3D%20%20foo%20bar%20%20%3D%3D%3E&b=%23%23%23Hello%20World%23%23%23',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with single quotes()
    {
        $event = [
            'version' => '2.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            'rawPath' => '/hello',
            'rawQueryString' => 'firstname=Billy&surname=O%27Reilly',
            'queryStringParameters' => [
                'firstname' => 'Billy',
                'surname' => 'O\'Reilly',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [
                'firstname' => 'Billy',
                'surname' => 'O\'Reilly',
            ],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'firstname' => 'Billy',
                'surname' => 'O\'Reilly',
            ],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello?firstname=Billy&surname=O%27Reilly',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'firstname=Billy&surname=O%27Reilly',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with backslash characters()
    {
        $event = [
            'version' => '2.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            'rawPath' => '/hello',
            'rawQueryString' => 'sum=10%5c2%3d5',
            'queryStringParameters' => [
                'sum' => '10\\2=5',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [
                'sum' => '10\\2=5',
            ],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'sum' => '10\\2=5',
            ],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello?sum=10%5C2%3D5',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'sum=10%5C2%3D5',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with null characters()
    {
        $event = [
            'version' => '2.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            'rawPath' => '/hello',
            'rawQueryString' => 'str=A%20string%20with%20containing%20%00%00%00%20nulls',
            'queryStringParameters' => [
                'str' => "A string with containing \0\0\0 nulls",
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [
                'str' => "A string with containing \0\0\0 nulls",
            ],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'str' => "A string with containing \0\0\0 nulls",
            ],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello?str=A%20string%20with%20containing%20%00%00%00%20nulls',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'str=A%20string%20with%20containing%20%00%00%00%20nulls',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with badly formed string()
    {
        $event = [
            'version' => '2.0',
            'httpMethod' => 'GET',
            'path' => '/hello',
            'rawPath' => '/hello',
            'rawQueryString' => 'arr[1=sid&arr[4][2=fred',
            'queryStringParameters' => [
                'arr[1' => 'sid',
                'arr[4][2' => 'fred',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [
                'arr_1' => 'sid',
                'arr' => [
                    '4' => 'fred',
                ],
            ],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [],
            '$_REQUEST' => [
                'arr_1' => 'sid',
                'arr' => [
                    '4' => 'fred',
                ],
            ],
            '$_SERVER' => [
                'REQUEST_URI' => '/hello?arr_1=sid&arr%5B4%5D=fred',
                'PHP_SELF' => '/hello',
                'PATH_INFO' => '/hello',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => 'arr_1=sid&arr%5B4%5D=fred',
                'CONTENT_LENGTH' => '0',
                'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
                'LAMBDA_INVOCATION_CONTEXT' => json_encode($this->fakeContext),
                'LAMBDA_REQUEST_CONTEXT' => '[]',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }
}
