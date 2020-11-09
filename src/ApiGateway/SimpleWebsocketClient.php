<?php declare(strict_types=1);

namespace Bref\ApiGateway;

use AsyncAws\Core\Request;
use AsyncAws\Core\Response;
use AsyncAws\Core\Stream\StreamFactory;
use Symfony\Component\HttpClient\HttpClient;

/**
 * A simple alternative for communicating with websocket connections.
 */
final class SimpleWebsocketClient
{
    /** @var string */
    private $apiId;

    /** @var string */
    private $region;

    /** @var string */
    private $stage;

    /** @var WebsocketClient */
    private $client;

    public function __construct(string $apiId, string $region, string $stage, int $timeout = 60)
    {
        $this->apiId = $apiId;
        $this->region = $region;
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

    public function disconnect(string $connectionId): Response
    {
        return $this->client->process(
            $this->request('DELETE', sprintf('/%s/@connections/%s', $this->stage, $connectionId))
        );
    }

    public function message(string $connectionId, string $body): Response
    {
        return $this->client->process(
            $this->request('POST', sprintf('/%s/@connections/%s', $this->stage, $connectionId), $body)
        );
    }

    public function status(string $connectionId): Response
    {
        return $this->client->process(
            $this->request('GET', sprintf('/%s/@connections/%s', $this->stage, $connectionId))
        );
    }

    private function request(string $method, string $url, string $body = null): Request
    {
        return new Request($method, $url, [], [], StreamFactory::create($body));
    }
}
