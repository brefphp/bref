<?php declare(strict_types=1);

namespace Bref\Websocket;

use AsyncAws\Core\Credentials\CredentialProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A simple alternative for communicating with websocket connections.
 */
final class SimpleWebsocketClient
{
    /** @var string */
    private $stage;

    /** @var WebsocketClient */
    private $client;

    public static function create(string $apiId, string $region, string $stage, int $timeout = 10): SimpleWebsocketClient
    {
        return new static(
            $apiId,
            $region,
            $stage,
            HttpClient::create([
                'timeout' => $timeout,
            ])
        );
    }

    /**
     * @internal Method only public for tests, use `create()` instead.
     */
    public function __construct(
        string $apiId,
        string $region,
        string $stage,
        HttpClientInterface $httpClient,
        ?CredentialProvider $credentialProvider = null
    ) {
        $this->stage = $stage;
        $this->client = new WebsocketClient(
            [
                'region' => $region,
                'endpoint' => sprintf('https://%s.execute-api.%s.amazonaws.com', $apiId, $region),
            ],
            $credentialProvider,
            $httpClient
        );
    }

    public function disconnect(string $connectionId): void
    {
        $this->client->request('DELETE', sprintf('/%s/@connections/%s', $this->stage, $connectionId));
    }

    public function message(string $connectionId, string $body): void
    {
        $this->client->request('POST', sprintf('/%s/@connections/%s', $this->stage, $connectionId), $body);
    }

    public function status(string $connectionId): WebsocketClientStatus
    {
        return new WebsocketClientStatus(
            $this->client->request('GET', sprintf('/%s/@connections/%s', $this->stage, $connectionId))
                ->toArray()
        );
    }
}
