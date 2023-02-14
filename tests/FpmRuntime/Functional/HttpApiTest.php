<?php declare(strict_types=1);

namespace Bref\Test\FpmRuntime\Functional;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

class HttpApiTest extends TestCase
{
    private Client $http;

    public function setUp(): void
    {
        parent::setUp();

        $this->http = new Client([
            'base_uri' => 'https://3ipdsvypt1.execute-api.eu-west-1.amazonaws.com/',
            'http_errors' => false,
        ]);
    }

    public function test supports multiple cookies with API Gateway format v2()
    {
        $response = $this->http->request('GET');

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals(['foo', 'bar'], $response->getHeader('Set-Cookie'));
    }
}
