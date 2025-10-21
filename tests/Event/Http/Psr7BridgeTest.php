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

    public function test I can convert a request from an event with body multipart data type()
    {
        $datav1 = [
            'version' => '1.0',
            'resource' => '/path',
            'path' => '/path',
            'httpMethod' => 'POST',
            'headers' => [
                'Accept' => '*/*',
                'Accept-Encoding' => 'gzip, deflate',
                'Cache-Control' => 'no-cache',
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
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
            'body' => "--testBoundary\r\nContent-Disposition: form-data; name=\"content\"\r\n\r\n<h1>Test content</h1>\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"some_id\"\r\n\r\n3034\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[0][other_id]\"\r\n\r\n4390954279\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[0][url]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[1][other_id]\"\r\n\r\n4313323164\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[1][url]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[2][other_id]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[2][url]\"\r\n\r\nhttps://someurl.com/node/745911\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"tags[0]\"\r\n\r\npublic health\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"tags[1]\"\r\n\r\npublic finance\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"_method\"\r\n\r\nPATCH\r\n--testBoundary--\r\n",
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
                'Content-Type' => 'multipart/form-data; boundary=testBoundary',
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
            'body' => "--testBoundary\r\nContent-Disposition: form-data; name=\"content\"\r\n\r\n<h1>Test content</h1>\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"some_id\"\r\n\r\n3034\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[0][other_id]\"\r\n\r\n4390954279\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[0][url]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[1][other_id]\"\r\n\r\n4313323164\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[1][url]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[2][other_id]\"\r\n\r\n\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"references[2][url]\"\r\n\r\nhttps://someurl.com/node/745911\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"tags[0]\"\r\n\r\npublic health\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"tags[1]\"\r\n\r\npublic finance\r\n--testBoundary\r\nContent-Disposition: form-data; name=\"_method\"\r\n\r\nPATCH\r\n--testBoundary--\r\n",
            'isBase64Encoded' => false,
        ];

        $expectedBody = [
            'content' => '<h1>Test content</h1>',
            'some_id' => '3034',
            'references' => [
                [
                    'other_id' => '4390954279',
                    'url' => '',
                ],
                [
                    'other_id' => '4313323164',
                    'url' => '',
                ],
                [
                    'other_id' => '',
                    'url' => 'https://someurl.com/node/745911',
                ],
            ],
            'tags' => [
                'public health',
                'public finance',
            ],
            '_method' => 'PATCH',
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
