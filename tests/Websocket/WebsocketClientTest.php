<?php declare(strict_types=1);

namespace Bref\Test\Websocket;

use AsyncAws\Core\Credentials\NullProvider;
use AsyncAws\Core\Exception\Http\ClientException;
use Bref\Websocket\SimpleWebsocketClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class WebsocketClientTest extends TestCase
{
    public function test_message_success(): void
    {
        $request = json_decode(file_get_contents(__DIR__ . '/Samples/message-success.json'), true);
        $client = $this->getClient($request);

        $client->message('WDGT8fY4joECJrQ=', 'ping');
        $this->assertTrue(true, 'Assertion to mark that no exception was thrown');
    }

    public function test_message_failure(): void
    {
        $request = json_decode(file_get_contents(__DIR__ . '/Samples/message-failure-invalid-connectionid.json'), true);
        $client = $this->getClient($request);

        $this->expectException(ClientException::class);
        $this->expectExceptionMessage('Invalid connectionId: WDGT8fY4joECJrQ=test');
        $this->expectExceptionCode(400);
        $client->message('WDGT8fY4joECJrQ=test', 'ping');
    }

    public function test_disconnect_success(): void
    {
        $request = json_decode(file_get_contents(__DIR__ . '/Samples/disconnect-success.json'), true);
        $client = $this->getClient($request);

        $client->disconnect('WDGT8fY4joECJrQ=test');
        $this->assertTrue(true, 'Assertion to mark that no exception was thrown');
    }

    public function test_disconnect_failure(): void
    {
        $request = json_decode(file_get_contents(__DIR__ . '/Samples/disconnect-failure-gone.json'), true);
        $client = $this->getClient($request);

        $this->expectException(ClientException::class);
        $this->expectExceptionCode(410);
        $client->disconnect('WDGT8fY4joECJrQ=test');
    }

    public function test_status(): void
    {
        $request = json_decode(file_get_contents(__DIR__ . '/Samples/status.json'), true);
        $client = $this->getClient($request);

        $status = $client->status('WDGT8fY4joECJrQ=');
        $this->assertSame('1.2.3.4', $status->getSourceIp());
        $this->assertSame('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36', $status->getUserAgent());
        $this->assertSame(1605442994, $status->getConnectedAt()->getTimestamp());
        $this->assertSame(1605442994, $status->getLastActiveAt()->getTimestamp());

        $info = $status->toArray();
        $this->assertArrayHasKey('sourceIp', $info);
        $this->assertArrayHasKey('userAgent', $info);
        $this->assertArrayHasKey('connectedAt', $info);
        $this->assertArrayHasKey('lastActiveAt', $info);
    }

    /**
     * Construct a SimpleWebsocketClient from sample request.
     */
    private function getClient(array $request): SimpleWebsocketClient
    {
        return new SimpleWebsocketClient(
            'apiId',
            'eu-west-1',
            'dev',
            new MockHttpClient(
                [
                    new MockResponse($request['body'], [
                        'http_code' => $request['status_code'],
                    ]),
                ]
            ),
            new NullProvider
        );
    }
}
