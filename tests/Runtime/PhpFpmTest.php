<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Http\LambdaResponse;
use Bref\Runtime\FastCgiCommunicationFailed;
use Bref\Runtime\PhpFpm;
use Bref\Test\HttpRequestProxyTest;
use PHPUnit\Framework\TestCase;

class PhpFpmTest extends TestCase implements HttpRequestProxyTest
{
    /** @var PhpFpm|null */
    private $fpm;

    public function tearDown()
    {
        $this->fpm->stop();
    }

    public function test simple request()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with query string()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with arrays in query string()
    {
        $event = [
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
                'QUERY_STRING' => 'vars%5Bval1%5D=foo&vars%5Bval2%5D%5B0%5D=bar',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with custom header()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test POST request with raw body()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '"Hello world!"',
        ]);
    }

    public function test POST request with form data()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => 'foo=bar&bim=baz',
        ]);
    }

    /**
     * @see https://github.com/mnapoli/bref/issues/162
     */
    public function test POST request with body and no content length()
    {
        $event = [
            'httpMethod' => 'POST',
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
                'REQUEST_METHOD' => 'POST',
                'QUERY_STRING' => '',
                'HTTP_CONTENT_TYPE' => 'application/json',
                'HTTP_CONTENT_LENGTH' => '14',
            ],
            'HTTP_RAW_BODY' => '"Hello world!"',
        ]);
    }

    public function test POST request supports utf8 characters in body()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => 'Hello 🌍',
        ]);
    }

    public function test the content type header is not case sensitive()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '{}',
        ]);
    }

    public function test POST request with multipart form data()
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test POST request with multipart form data containing arrays()
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test request with cookies()
    {
        $event = [
            'httpMethod' => 'GET',
            'headers' => [
                'Cookie' => 'tz=Europe%2FParis; four=two+%2B+2; theme=light',
            ],
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [],
            '$_COOKIE' => [
                'tz' => 'Europe/Paris',
                'four' => 'two + 2',
                'theme' => 'light',
            ],
            '$_REQUEST' => [],
            '$_SERVER' => [
                'REQUEST_URI' => '/',
                'PHP_SELF' => '/',
                'PATH_INFO' => '/',
                'REQUEST_METHOD' => 'GET',
                'QUERY_STRING' => '',
                'HTTP_COOKIE' => 'tz=Europe%2FParis; four=two+%2B+2; theme=light',
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test POST request with multipart file uploads()
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
            'httpMethod' => 'POST',
            'headers' => [
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
                'Content-Length' => mb_strlen($body),
            ],
            'body' => $body,
        ];
        $this->assertGlobalVariables($event, [
            '$_GET' => [],
            '$_POST' => [],
            '$_FILES' => [
                'foo' => [
                    'name' => 'lorem.txt',
                    'type' => 'text/plain',
                    'error' => 0,
                    'size' => 57,
                    'content' => "Lorem ipsum dolor sit amet,\nconsectetur adipiscing elit.\n",
                ],
                'bar' => [
                    'name' => 'cars.csv',
                    'type' => '',
                    'error' => 0,
                    'size' => 51,
                    'content' => "Year,Make,Model\n1997,Ford,E350\n2000,Mercury,Cougar\n",
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test POST request with base64 encoded body()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => 'foo=bar',
        ]);
    }

    public function test HTTP_HOST header()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test PUT request()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test PATCH request()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test DELETE request()
    {
        $event = [
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
            ],
            'HTTP_RAW_BODY' => '',
        ]);
    }

    public function test OPTIONS request()
    {
        $event = [
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
            'httpMethod' => 'GET',
            'queryStringParameters' => [
                'code' => $expectedStatusCode,
            ],
        ])->toApiGatewayFormat()['statusCode'];

        self::assertEquals($expectedStatusCode, $statusCode);
    }

    public function provideStatusCodes(): array
    {
        return [[200], [301], [302], [400], [401], [403], [404], [500], [504]];
    }

    public function test response with headers()
    {
        $response = $this->get('response-headers.php', [
            'httpMethod' => 'GET',
        ])->toApiGatewayFormat();

        self::assertStringStartsWith('PHP/', $response['headers']['x-powered-by'] ?? '');
        unset($response['headers']['x-powered-by']);
        self::assertEquals([
            'content-type' => 'application/json',
        ], $response['headers']);
    }

    public function test response with cookies()
    {
        $cookieHeader = $this->get('cookies.php')->toApiGatewayFormat()['headers']['set-cookie'];

        self::assertEquals('MyCookie=MyValue; expires=Fri, 12-Jan-2018 08:32:03 GMT; Max-Age=0; path=/hello/; domain=example.com; secure; HttpOnly', $cookieHeader);
    }

    public function test response with error_log()
    {
        $response = $this->get('error.php')->toApiGatewayFormat();

        self::assertStringStartsWith('PHP/', $response['headers']['x-powered-by'] ?? '');
        unset($response['headers']['x-powered-by']);
        self::assertEquals([
            'content-type' => 'text/html; charset=UTF-8',
        ], $response['headers']);
    }

    public function test timeouts are recovered from()
    {
        $this->fpm = new PhpFpm(__DIR__ . '/PhpFpm/timeout.php', __DIR__ . '/PhpFpm/php-fpm.conf');
        $this->fpm->start();

        try {
            $this->fpm->proxy([
                'httpMethod' => 'GET',
                'queryStringParameters' => [
                    'timeout' => 10,
                ],
            ]);
            $this->fail('No exception was thrown');
        } catch (FastCgiCommunicationFailed $e) {
            // No way to salvage the second broken request, but this time PHP-FPM will be restarted
            // PHP-FPM should work after that
            $statusCode = $this->fpm->proxy(['httpMethod' => 'GET'])
                ->toApiGatewayFormat()['statusCode'];
            self::assertEquals(200, $statusCode);
        }
    }

    private function assertGlobalVariables(array $event, array $expectedGlobalVariables): void
    {
        $this->startFpm(__DIR__ . '/PhpFpm/request.php');
        $response = $this->fpm->proxy($event);

        $response = json_decode($response->toApiGatewayFormat()['body'], true);

        // Test global variables that cannot be hardcoded
        self::assertNotEmpty($response['$_SERVER']['HOME']);
        unset($response['$_SERVER']['HOME']);
        self::assertEquals(microtime(true), $response['$_SERVER']['REQUEST_TIME_FLOAT'], '', 5);
        unset($response['$_SERVER']['REQUEST_TIME_FLOAT']);
        self::assertEquals(time(), $response['$_SERVER']['REQUEST_TIME'], '', 5);
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
            'SCRIPT_FILENAME' => __DIR__ . '/PhpFpm/request.php',
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

    private function get(string $file, ?array $event = null): LambdaResponse
    {
        $this->startFpm(__DIR__ . '/PhpFpm/' . $file);

        return $this->fpm->proxy($event ?? [
            'httpMethod' => 'GET',
        ]);
    }

    private function startFpm(string $handler): void
    {
        if ($this->fpm) {
            $this->fpm->stop();
        }
        $this->fpm = new PhpFpm($handler, __DIR__ . '/PhpFpm/php-fpm.conf');
        $this->fpm->start();
    }
}
