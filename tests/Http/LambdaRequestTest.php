<?php declare(strict_types=1);

namespace Bref\Test\Http;

use Bref\Http\LambdaRequest;
use GuzzleHttp\Psr7\ServerRequest as Psr7Request;
use GuzzleHttp\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Request as SfRequest;
use function GuzzleHttp\Psr7\stream_for;

class LambdaRequestTest extends TestCase
{
    /**
     * This make sure we always pass the raw response
     *
     * @dataProvider rawEventProvider
     */
    public function testGetRawEvent(string $file, array $expected)
    {
        $request = $this->getRequestFromJsonFile($file);

        $this->assertEquals($expected, $request->getRawEvent());
    }

    /**
     * @dataProvider symfonyRequestProvider
     */
    public function testGetSymfonyEvent(string $file, SfRequest $expected)
    {
        $request = $this->getRequestFromJsonFile($file);

        $output = $request->getSymfonyRequest();
        $this->assertEquals($expected, $output);
    }

    /**
     * @dataProvider psr7RequestProvider
     */
    public function testGetPsr7Event(string $file, ServerRequestInterface $expected)
    {
        $request = $this->getRequestFromJsonFile($file);
        $psr7Request = $request->getPsr7Request();

        // Compare request but not bodies
        $dummyStream = stream_for('');
        $this->assertEquals($expected->withBody($dummyStream), $psr7Request->withBody($dummyStream));

        // Compare bodies
        $this->assertEquals($expected->getBody()->__toString(), $psr7Request->getBody()->__toString());
    }

    /**
     * This will automatically find all lambdaRequest*.json files in the fixture folder.
     */
    public function rawEventProvider()
    {
        $dir = dirname(__DIR__) . '/Fixture/Http/';
        foreach (glob($dir . 'lambdaRequest*.json') as $path) {
            yield basename($path) => [$path, json_decode(file_get_contents($path), true)];
        }
    }

    public function symfonyRequestProvider()
    {
        $dir = dirname(__DIR__) . '/Fixture/Http/';

        $request = SfRequest::create('/hello-world', 'GET', [], ['PHPSESSID' => '7tk4oc6dsa6e4chai4sljcffha'], [], [], '');
        $request->headers->add($this->getDefaultHeaders());
        yield 'lambdaRequest0.json' => [$dir . 'lambdaRequest0.json', $request];

        $request = SfRequest::create('/multipart-post?foo[]=bar&foo[]=baz&foobar=baz&test', 'POST', [], [], [], [], '--578de3b0e3c46.2334ba3
Content-Disposition: form-data; name="foo"
Content-Length: 15

A normal stream
--578de3b0e3c46.2334ba3
Content-Type: text/plain
Content-Disposition: form-data; name="baz"
Content-Length: 6

string
--578de3b0e3c46.2334ba3--');

        $request->headers->add([
            'Content-Type'=> 'multipart/form-data; boundary="578de3b0e3c46.2334ba3"',
            'Host'=> 'example.com',
        ]);

        yield 'lambdaRequest1.json' => [$dir . 'lambdaRequest1.json', $request];
    }

    public function psr7RequestProvider()
    {
        $dir = dirname(__DIR__) . '/Fixture/Http/';

        $request = new Psr7Request('GET', '/hello-world', $this->getDefaultHeaders(), $this->createStream(''), '1.1', [
            'SERVER_PROTOCOL' => '1.1',
            'REQUEST_METHOD' => 'GET',
            'REQUEST_TIME' => 1568547468262,
            'QUERY_STRING' => '',
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_URI' => '/hello-world',
            'HTTP_HOST' => 'abc123.execute-api.eu-west-1.amazonaws.com',
        ]);
        $request = $request->withCookieParams(['PHPSESSID' => '7tk4oc6dsa6e4chai4sljcffha']);

        yield 'lambdaRequest0.json' => [$dir . 'lambdaRequest0.json', $request];

        $request = new Psr7Request(
            'POST',
            '/multipart-post',
            [
                'Content-Type'=> 'multipart/form-data; boundary="578de3b0e3c46.2334ba3"',
                'Host'=> 'example.com',
            ],
            $this->createStream('--578de3b0e3c46.2334ba3
Content-Disposition: form-data; name="foo"
Content-Length: 15

A normal stream
--578de3b0e3c46.2334ba3
Content-Type: text/plain
Content-Disposition: form-data; name="baz"
Content-Length: 6

string
--578de3b0e3c46.2334ba3--'),
            '1.1',
            [
                'SERVER_PROTOCOL' => '1.1',
                'REQUEST_METHOD' => 'POST',
                'REQUEST_TIME' => 1568562989871,
                'QUERY_STRING' => 'foo[]=bar&foo[]=baz&foobar=baz&test',
                'DOCUMENT_ROOT' => getcwd(),
                'REQUEST_URI' => '/multipart-post',
                'HTTP_HOST' => 'example.com',
            ]
        );
        $request = $request->withParsedBody(['foo' => 'A normal stream', 'baz' => 'string']);
        $request = $request->withQueryParams(['foo' => ['bar', 'baz'], 'foobar' => 'baz', 'test' => '']);
        yield 'lambdaRequest1.json' => [$dir . 'lambdaRequest1.json', $request];
    }

    private function getRequestFromJsonFile(string $file): LambdaRequest
    {
        $array = json_decode(file_get_contents($file), true);

        return LambdaRequest::create($array);
    }

    private function getDefaultHeaders()
    {
        return [
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Accept-Language' => 'en-US,en;q=0.9,sv;q=0.8,la;q=0.7',
            'CloudFront-Forwarded-Proto' => 'https',
            'CloudFront-Is-Desktop-Viewer' => 'true',
            'CloudFront-Is-Mobile-Viewer' => 'false',
            'CloudFront-Is-SmartTV-Viewer' => 'false',
            'CloudFront-Is-Tablet-Viewer' => 'false',
            'CloudFront-Viewer-Country' => 'SE',
            'Cookie' => 'PHPSESSID=7tk4oc6dsa6e4chai4sljcffha',
            'Host' => 'abc123.execute-api.eu-west-1.amazonaws.com',
            'sec-fetch-mode' => 'navigate',
            'sec-fetch-site' => 'none',
            'sec-fetch-user' => '?1',
            'upgrade-insecure-requests' => '1',
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.75 Safari/537.36',
            'Via' => '2.0 0123456789.cloudfront.net (CloudFront)',
            'X-Amz-Cf-Id' => 'uT34zMm8aYW-q3RC_Z6_NMYu5kQxoxpMJOPfJy-Vd1hqjp9ak5VxLg==',
            'X-Amzn-Trace-Id' => 'Root=1-5d7e228c-2e1603a0d1c5aec07d8e1eb0',
            'X-Forwarded-For' => '22.22.22.22, 11.11.11.11',
            'X-Forwarded-Port' => '443',
            'X-Forwarded-Proto' => 'https',
        ];
    }

    private function createStream(string $body): StreamInterface
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $body);
        rewind($stream);

        return new Stream($stream);
    }
}
