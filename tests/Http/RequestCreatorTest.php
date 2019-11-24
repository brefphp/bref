<?php declare(strict_types=1);

namespace Bref\Test\Http;

use Bref\Context\Context;
use Bref\Http\RequestCreator;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UploadedFileInterface;

/**
 * @covers \Bref\Http\RequestCreator
 */
class RequestCreatorTest extends TestCase
{
    public function test simple cookies are supported()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest(
            $this->getSampleRequest('get_with_cookies.json'),
            $this->getContext()
        );

        $this->assertEquals([
            'name' => 'value',
            'name2' => 'value2',
            'name3' => 'value3',
        ], $request->getCookieParams());
    }

    /**
     * Data copied from a large news site
     */
    public function test complex cookies are supported()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest(
            $this->getSampleRequest('post_with_complex_cookies.json'),
            $this->getContext()
        );
        $cookieParams = $request->getCookieParams();

        $this->assertEquals([
            '_lp4_u' => '3BIWA3Ffff',
            'psi_notification_version' => '12',
            'psi_show' => '0',
            'psi_timestamp' => '1572425342924',
            'psi_zero_interactions' => '1',
            '__gads' => 'ID=3722b532ffffffff:T=1572521257:S=ALNI_MaNukL8IgfDpxcyHfCdlR9Aaaaaaa',
            '_lp4_c' => '',
            'clientBucket' => '41',
            '_pulse2data' => 'ffffffff-aad0-42bf-b7d6-3110a0ffe0dc,v,,1574527669320,eyJpc3N1ZWRBdCI6IjIwMTktMTAtaaaaaaaaNDk6MDJaIiwiZW5jIjoiQTEyOENCQy1IUzI1NiIsImFsZyI6ImRpciIsImtpZCI6IjIifQ..7A4raaaaa-jtCYbCy6Wf0g.AIybt7VoYbXNsQFgbvq6APAu1ugjYGwnpGgviUodRaaaaa-zR0AA4khkwX8hItxQ4SI0cada-nYapHIBaaaaaWUC087_fGKv66x-yCfwoZ14kvxkOZ1LANvxGhb1LFrTaaaaaSUHZoSKMv3HbihR7cHtmzHjMBh_8_95HSxA1tauxhjaaaaaaZNlmt8lkvD6ZiqaqO1Kmw3q9taXPhyuf70DqV1Ev_s4J95BSGZX6sQ.aaaaaaaaaa9VGF62T_Y6Zg,8011187128553086469,1574541169320,true,,eaaaaaaaaiIyIiwiYWxnIjoiSFMyNTYifQ..VEPJ5cs3BJJhaaaaaaaadCJwWj5CdhgEPby-OaWMLOQ',
        ], $cookieParams);
    }

    public function test it can parse a complex query string()
    {
        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => [
                'sub1' => 'really!;strange!!!&string',
                'sub2' => 'value4',
            ],
            'var' => [
                '1',
                '2',
            ],
        ];

        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('get_with_query_string.json'), $this->getContext());

        $queryParams = $request->getQueryParams();

        $this->assertEquals($expected, $queryParams);
    }

    public function test it can contain a JSON body()
    {
        $expected = [
            'key1' => 'value1',
        ];

        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('put_with_json_body.json'), $this->getContext());

        $bodyContents = (string) $request->getBody();

        $this->assertJson($bodyContents);
        $data = json_decode($bodyContents, true);
        $this->assertEquals($expected, $data);
    }


    public function test create basic request()
    {
        $currentTimestamp = time();

        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('basic_request.json'), $this->getContext());

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals(['foo' => 'bar', 'bim' => 'baz'], $request->getQueryParams());
        $this->assertEquals('1.1', $request->getProtocolVersion());
        $this->assertEquals('/test', $request->getUri()->getPath());
        $this->assertEquals('', $request->getBody()->getContents());
        $this->assertArrayHasKey('event', $request->getAttributes());
        $this->assertArrayHasKey('context', $request->getAttributes());
        $serverParams = $request->getServerParams();

        $this->assertEquals('1.1', $serverParams['SERVER_PROTOCOL']);
        $this->assertEquals('GET', $serverParams['REQUEST_METHOD']);
        $this->assertEquals('1574617740', $serverParams['REQUEST_TIME']);
        $this->assertEquals('foo=bar&bim=baz', $serverParams['QUERY_STRING']);
        $this->assertEquals('/test', $serverParams['REQUEST_URI']);

        $this->assertEquals('/test?foo=bar&bim=baz', $request->getRequestTarget());
        $this->assertEquals([], $request->getHeaders());
    }

    public function test non empty body()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('non_empty_body.json'), $this->getContext());

        $this->assertEquals('test test test', $request->getBody()->getContents());
    }

    public function test POST body is parsed()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('with_post_body.json'), $this->getContext());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(['foo' => 'bar', 'bim' => 'baz'], $request->getParsedBody());
    }

    public function test the content type header is not case sensitive()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('case_insensitive_header.json'), $this->getContext());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(['foo' => 'bar', 'bim' => 'baz'], $request->getParsedBody());
    }

    /**
     * Don't parse JSON body
     *
     * @see https://github.com/brefphp/bref/issues/1#issuecomment-387635149
     */
    public function test POST JSON body is not parsed()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('json_post_not_parsed.json'), $this->getContext());

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(null, $request->getParsedBody());
        $this->assertEquals(['foo' => 'bar'], json_decode($request->getBody()->getContents(), true));
    }

    public function test multipart form data is supported()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('with_multipart_form_data.json'), $this->getContext());

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(['foo' => 'bar', 'bim' => 'baz'], $request->getParsedBody());
    }

    public function test cookies are supported()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('with_cookies.json'), $this->getContext());

        $this->assertEquals([
            'tz' => 'Europe/Paris',
            'four' => 'two + 2',
            'theme' => 'light',
        ], $request->getCookieParams());
    }

    public function test arrays in query string are supported()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest(
            $this->getSampleRequest('with_array_in_query_string.json'),
            $this->getContext()
        );

        $this->assertEquals([
            'vars' => [
                'val1' => 'foo',
                'val2' => ['bar'],
            ],
        ], $request->getQueryParams());
    }


    public function test arrays in multivalue query string are supported()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest(
            $this->getSampleRequest('with_array_in_multivalue_query_string.json'),
            $this->getContext()
        );

        $this->assertEquals([
            'vars' => [
                'val1' => 'foo',
                'val2' => ['bar', 'baz'],
            ],
        ], $request->getQueryParams());
    }

    public function test arrays in name are supported with multipart form data()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('multipart_form_data_with_arrays.json'), $this->getContext());

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(
            [
                'delete' => [
                    'categories' => [
                        '123',
                        '456',
                        'named' => 'value3', // phpcs:ignore Squiz.Arrays.ArrayDeclaration.KeySpecified
                    ],
                    'test' => 'value4',
                ],
            ],
            $request->getParsedBody()
        );
    }

    public function test files are supported with multipart form data()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('multipart_file_data.json'), $this->getContext());

        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals([], $request->getParsedBody());
        $this->assertEquals(['foo', 'bar'], array_keys($request->getUploadedFiles()));

        /** @var UploadedFileInterface $foo */
        $foo = $request->getUploadedFiles()['foo'];
        $this->assertInstanceOf(UploadedFileInterface::class, $foo);
        $this->assertEquals('lorem.txt', $foo->getClientFilename());
        $this->assertEquals('text/plain', $foo->getClientMediaType());
        $this->assertEquals(UPLOAD_ERR_OK, $foo->getError());
        $this->assertEquals(57, $foo->getSize());
        $this->assertStringEqualsFile(
            dirname(__FILE__, 2) . '/Fixture/Psr7/sample/lipsum.txt',
            $foo->getStream()->getContents()
        );

        /** @var UploadedFileInterface $bar */
        $bar = $request->getUploadedFiles()['bar'];
        $this->assertInstanceOf(UploadedFileInterface::class, $bar);
        $this->assertEquals('cars.csv', $bar->getClientFilename());
        $this->assertEquals(
            'application/octet-stream',
            $bar->getClientMediaType()
        ); // not set: fallback to application/octet-stream
        $this->assertEquals(UPLOAD_ERR_OK, $bar->getError());
        $this->assertEquals(51, $bar->getSize());
        $this->assertStringEqualsFile(
            dirname(__FILE__, 2) . '/Fixture/Psr7/sample/cars.csv',
            $bar->getStream()->getContents()
        );
    }

    public function test POST base64 encoded body is supported()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('with_base64_body.json'), $this->getContext());
        $this->assertEquals('POST', $request->getMethod());
        $this->assertEquals(['foo' => 'bar'], $request->getParsedBody());
    }

    public function test HTTP_HOST is set()
    {
        $creator = new RequestCreator;
        $request = $creator->createRequest($this->getSampleRequest('http_host_is_set.json'), $this->getContext());

        $serverParams = $request->getServerParams();

        $this->assertSame('www.example.com', $serverParams['HTTP_HOST']);
    }

    private function getSampleRequest(string $filename): array
    {
        $jsonData = file_get_contents(sprintf(
            '%s/Fixture/Psr7/sample/%s',
            dirname(__FILE__, 2),
            $filename
        ));
        return json_decode($jsonData, true);
    }

    public function storeSampleRequest(string $filename, array $request)
    {
        $filepath = sprintf(
            '%s/Fixture/Psr7/sample/%s',
            dirname(__FILE__, 2),
            $filename
        );
        file_put_contents($filepath, json_encode($request, JSON_PRETTY_PRINT));
    }

    protected function getContext()
    {
        return new Context('1', 5000, 'arn::', 'trace');
    }
}
