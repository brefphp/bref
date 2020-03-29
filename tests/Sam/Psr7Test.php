<?php declare(strict_types=1);

namespace Bref\Test\Sam;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Process\Process;

class Psr7Test extends TestCase
{
    /** @var string */
    private $logs;

    public function setUp(): void
    {
        parent::setUp();
        $this->logs = '';
    }

    public function test simple invocation()
    {
        $response = $this->invoke('/psr7');

        $this->assertResponseSuccessful($response);
        self::assertEquals('Hello world!', $this->getBody($response), $this->logs);
    }

    private function invoke(string $url): ResponseInterface
    {
        $api = new Process(['sam', 'local', 'start-api', '--region', 'us-east-1']);
        $api->setWorkingDirectory(__DIR__);
        $api->setTimeout(0.);
        $api->start();
        $api->waitUntil(function ($type, $output) {
            return strpos($output, 'Running on http://127.0.0.1:3000/') !== false;
        });

        try {
            $http = new Client([
                'base_uri' => 'http://127.0.0.1:3000',
                'http_errors' => false,
            ]);
            $response = $http->request('GET', $url);
        } finally {
            $api->stop();
            $this->logs = $api->getErrorOutput() . $api->getOutput();
        }

        return $response;
    }

    private function assertResponseSuccessful(ResponseInterface $response): void
    {
        self::assertSame(200, $response->getStatusCode(), $this->logs);
    }

    private function getBody(ResponseInterface $response): string
    {
        return $response->getBody()->getContents();
    }
}
