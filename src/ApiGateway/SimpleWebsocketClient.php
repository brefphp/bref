<?php declare(strict_types=1);

namespace Bref\ApiGateway;

use AsyncAws\Core\Request;
use AsyncAws\Core\Stream\StreamFactory;
use Symfony\Component\HttpClient\HttpClient;

/**
 * A simple alternative for communicating with websocket connections.
 */
final class SimpleWebsocketClient
{
    /** @var string */
    private $stage;

    /** @var WebsocketClient */
    private $client;

    public function __construct(string $apiId, string $region, string $stage, int $timeout = 10)
    {
        $this->stage = $stage;
        $this->client = new WebsocketClient(
            [
                'region' => $region,
                'endpoint' => sprintf('https://%s.execute-api.%s.amazonaws.com', $apiId, $region),
            ],
            null,
            HttpClient::create([
                'timeout' => $timeout,
            ])
        );
    }

    public function disconnect(string $connectionId): bool
    {
        return $this->client->process(
            $this->request('DELETE', sprintf('/%s/@connections/%s', $this->stage, $connectionId))
        )->getStatusCode() === 200;
    }

    public function message(string $connectionId, string $body): bool
    {
        return $this->client->process(
            $this->request('POST', sprintf('/%s/@connections/%s', $this->stage, $connectionId), $body)
        )->getStatusCode() === 200;
    }

    public function status(string $connectionId): WebsocketClientStatus
    {
        return new WebsocketClientStatus(
            $this->client->process(
                $this->request('GET', sprintf('/%s/@connections/%s', $this->stage, $connectionId))
            )->toArray()
        );
    }

    private function request(string $method, string $url, ?string $body = null): Request
    {
        return new Request($method, $url, [], [], StreamFactory::create($body));
    }
}
