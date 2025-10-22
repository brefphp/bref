<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\Psr7Bridge;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

use function assert;

class Psr7BridgeTest extends CommonHttpTest
{
    private ServerRequestInterface $request;

    public function test I can create a response from a PSR7 response()
    {
        $psr7Response = new Response(404, [
            'Content-Type' => 'application/json',
        ], json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR));

        $response = Psr7Bridge::convertResponse($psr7Response);
        self::assertSame([
            'isBase64Encoded' => false,
            'statusCode' => 404,
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode(['foo' => 'bar'], JSON_THROW_ON_ERROR),
        ], $response->toApiGatewayFormat());
    }

    public function test I can convert a request from an event with complex multipart form data structures()
    {
        $body = "--complexBoundary\r\nContent-Disposition: form-data; name=\"simple_string\"\r\n\r\nHello World\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"empty_string\"\r\n\r\n\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"numeric_string\"\r\n\r\n12345\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"boolean_string\"\r\n\r\ntrue\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"indexed_array[0]\"\r\n\r\nfirst_item\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"indexed_array[1]\"\r\n\r\nsecond_item\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"indexed_array[2]\"\r\n\r\nthird_item\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"associative_array[name]\"\r\n\r\nJohn Doe\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"associative_array[age]\"\r\n\r\n30\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"associative_array[email]\"\r\n\r\njohn@example.com\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"nested_objects[user][profile][first_name]\"\r\n\r\nJohn\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"nested_objects[user][profile][last_name]\"\r\n\r\nDoe\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"nested_objects[user][profile][age]\"\r\n\r\n30\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"nested_objects[user][settings][theme]\"\r\n\r\ndark\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"nested_objects[user][settings][notifications]\"\r\n\r\ntrue\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"nested_objects[company][name]\"\r\n\r\nAcme Corp\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"nested_objects[company][employees]\"\r\n\r\n150\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"mixed_arrays[0][id]\"\r\n\r\n1\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"mixed_arrays[0][name]\"\r\n\r\nItem One\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"mixed_arrays[0][tags][0]\"\r\n\r\ntag1\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"mixed_arrays[0][tags][1]\"\r\n\r\ntag2\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"mixed_arrays[1][id]\"\r\n\r\n2\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"mixed_arrays[1][name]\"\r\n\r\nItem Two\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"mixed_arrays[1][tags][0]\"\r\n\r\ntag3\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"mixed_arrays[1][tags][1]\"\r\n\r\ntag4\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"sparse_array[0]\"\r\n\r\nfirst\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"sparse_array[2]\"\r\n\r\nthird\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"sparse_array[5]\"\r\n\r\nsixth\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"string_keys[first_key]\"\r\n\r\nfirst_value\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"string_keys[second_key]\"\r\n\r\nsecond_value\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"numeric_keys[0]\"\r\n\r\nzero_value\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"numeric_keys[1]\"\r\n\r\none_value\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"numeric_keys[10]\"\r\n\r\nten_value\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"empty_values[empty_string]\"\r\n\r\n\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"empty_values[zero_string]\"\r\n\r\n0\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"empty_values[false_string]\"\r\n\r\nfalse\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"empty_values[null_string]\"\r\n\r\nnull\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"complex_nesting[level1][level2][level3][items][0][name]\"\r\n\r\nDeep Item 1\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"complex_nesting[level1][level2][level3][items][0][value]\"\r\n\r\n100\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"complex_nesting[level1][level2][level3][items][1][name]\"\r\n\r\nDeep Item 2\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"complex_nesting[level1][level2][level3][items][1][value]\"\r\n\r\n200\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"complex_nesting[level1][level2][level3][metadata][count]\"\r\n\r\n2\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"complex_nesting[level1][level2][level3][metadata][enabled]\"\r\n\r\ntrue\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"duplicate_keys[0]\"\r\n\r\nfirst_duplicate\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"duplicate_keys[0]\"\r\n\r\nsecond_duplicate\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"duplicate_keys[0]\"\r\n\r\nthird_duplicate\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"special_chars[with spaces]\"\r\n\r\nvalue with spaces\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"special_chars[with-dashes]\"\r\n\r\nvalue-with-dashes\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"special_chars[with_underscores]\"\r\n\r\nvalue_with_underscores\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"special_chars[with.dots]\"\r\n\r\nvalue.with.dots\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"unicode[测试]\"\r\n\r\n测试值\r\n--complexBoundary\r\nContent-Disposition: form-data; name=\"unicode[emoji]\"\r\n\r\n🚀🌟💻\r\n--complexBoundary--\r\n";

        $datav1 = [
            'version' => '1.0',
            'resource' => '/path',
            'path' => '/path',
            'httpMethod' => 'POST',
            'headers' => [
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip, deflate',
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'multipart/form-data; boundary=complexBoundary',
                'Host' => 'example.org',
                'User-Agent' => 'PostmanRuntime/7.20.1',
                'X-Amzn-Trace-Id' => 'Root=1-ffffffff-ffffffffffffffffffffffff',
                'X-Forwarded-For' => '1.1.1.1',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ],
            'queryStringParameters' => null,
            'pathParameters' => null,
            'stageVariables' => null,
            'requestContext' => [
                'resourceId' => 'xxxxxx',
                'resourcePath' => '/path',
                'httpMethod' => 'POST',
                'extendedRequestId' => 'XXXXXX-xxxxxxxx=',
                'requestTime' => '24/Nov/2019:18:55:08 +0000',
                'path' => '/path',
                'accountId' => '123400000000',
                'protocol' => 'HTTP/1.1',
                'stage' => 'dev',
                'domainPrefix' => 'dev',
                'requestTimeEpoch' => 1574621708700,
                'requestId' => 'ffffffff-ffff-4fff-ffff-ffffffffffff',
                'identity' => [
                    'cognitoIdentityPoolId' => null,
                    'accountId' => null,
                    'cognitoIdentityId' => null,
                    'caller' => null,
                    'sourceIp' => '1.1.1.1',
                    'principalOrgId' => null,
                    'accessKey' => null,
                    'cognitoAuthenticationType' => null,
                    'cognitoAuthenticationProvider' => null,
                    'userArn' => null,
                    'userAgent' => 'PostmanRuntime/7.20.1',
                    'user' => null,
                ],
                'domainName' => 'example.org',
                'apiId' => 'xxxxxxxxxx',
            ],
            'body' => $body,
            'isBase64Encoded' => false,
        ];

        $datav2 = [
            'version' => '2.0',
            'routeKey' => 'ANY /path',
            'rawPath' => '/path',
            'rawQueryString' => '',
            'headers' => [
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip, deflate',
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'multipart/form-data; boundary=complexBoundary',
                'Host' => 'example.org',
                'User-Agent' => 'PostmanRuntime/7.20.1',
                'X-Amzn-Trace-Id' => 'Root=1-ffffffff-ffffffffffffffffffffffff',
                'X-Forwarded-For' => '1.1.1.1',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-Proto' => 'https',
            ],
            'queryStringParameters' => null,
            'stageVariables' => null,
            'requestContext' => [
                'accountId' => '123400000000',
                'apiId' => 'xxxxxxxxxx',
                'domainName' => 'example.org',
                'domainPrefix' => '0000000000',
                'http' => [
                    'method' => 'POST',
                    'path' => '/path',
                    'protocol' => 'HTTP/1.1',
                    'sourceIp' => '1.1.1.1',
                    'userAgent' => 'PostmanRuntime/7.20.1',
                ],
                'requestId' => 'JTHoQgr2oAMEPMg=',
                'routeId' => '47matwk',
                'routeKey' => 'ANY /path',
                'stage' => '$default',
                'time' => '24/Nov/2019:18:55:08 +0000',
                'timeEpoch' => 1574621708700,
            ],
            'body' => $body,
            'isBase64Encoded' => false,
        ];

        $expectedBody = [
            'simple_string' => 'Hello World',
            'empty_string' => '',
            'numeric_string' => '12345',
            'boolean_string' => 'true',
            'indexed_array' => [
                'first_item',
                'second_item',
                'third_item',
            ],
            'associative_array' => [
                'name' => 'John Doe',
                'age' => '30',
                'email' => 'john@example.com',
            ],
            'nested_objects' => [
                'user' => [
                    'profile' => [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                        'age' => '30',
                    ],
                    'settings' => [
                        'theme' => 'dark',
                        'notifications' => 'true',
                    ],
                ],
                'company' => [
                    'name' => 'Acme Corp',
                    'employees' => '150',
                ],
            ],
            'mixed_arrays' => [
                [
                    'id' => '1',
                    'name' => 'Item One',
                    'tags' => [
                        'tag1',
                        'tag2',
                    ],
                ],
                [
                    'id' => '2',
                    'name' => 'Item Two',
                    'tags' => [
                        'tag3',
                        'tag4',
                    ],
                ],
            ],
            'sparse_array' => [
                0 => 'first',
                2 => 'third',
                5 => 'sixth',
            ],
            'string_keys' => [
                'first_key' => 'first_value',
                'second_key' => 'second_value',
            ],
            'numeric_keys' => [
                0 => 'zero_value',
                1 => 'one_value',
                10 => 'ten_value',
            ],
            'empty_values' => [
                'empty_string' => '',
                'zero_string' => '0',
                'false_string' => 'false',
                'null_string' => 'null',
            ],
            'complex_nesting' => [
                'level1' => [
                    'level2' => [
                        'level3' => [
                            'items' => [
                                [
                                    'name' => 'Deep Item 1',
                                    'value' => '100',
                                ],
                                [
                                    'name' => 'Deep Item 2',
                                    'value' => '200',
                                ],
                            ],
                            'metadata' => [
                                'count' => '2',
                                'enabled' => 'true',
                            ],
                        ],
                    ],
                ],
            ],
            'duplicate_keys' => [
                'first_duplicate',
                'second_duplicate',
                'third_duplicate',
            ],
            'special_chars' => [
                'with spaces' => 'value with spaces',
                'with-dashes' => 'value-with-dashes',
                'with_underscores' => 'value_with_underscores',
                'with.dots' => 'value.with.dots',
            ],
            'unicode' => [
                '??????' => '测试值',
                'emoji' => '🚀🌟💻',
            ],
        ];

        $eventv1 = new HttpRequestEvent($datav1);
        $requestv1 = Psr7Bridge::convertRequest($eventv1, Context::fake());
        $this->assertEquals($expectedBody, $requestv1->getParsedBody());

        $eventv2 = new HttpRequestEvent($datav2);
        $requestv2 = Psr7Bridge::convertRequest($eventv2, Context::fake());
        $this->assertEquals($expectedBody, $requestv2->getParsedBody());
    }

    protected function fromFixture(string $file): void
    {
        $event = new HttpRequestEvent(json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR));
        $this->request = Psr7Bridge::convertRequest($event, Context::fake());
    }

    protected function assertBody(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getBody()->getContents());
    }

    protected function assertContentType(?string $expected): void
    {
        $this->assertEquals($expected, $this->request->getHeaderLine('Content-Type'));
    }

    protected function assertCookies(array $expected, string |null $expectedHeader = null): void
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
        $this->assertEquals($expected, $this->request->getServerParams()['QUERY_STRING'] ?? '');
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

    protected function assertParsedBody(array | null $expected): void
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
        assert($uploadedFile instanceof UploadedFileInterface);
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

    protected function assertBasicAuthUser(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getServerParams()['PHP_AUTH_USER']);
    }

    protected function assertBasicAuthPassword(string $expected): void
    {
        $this->assertEquals($expected, $this->request->getServerParams()['PHP_AUTH_PW']);
    }
}
