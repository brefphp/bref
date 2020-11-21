<?php declare(strict_types=1);

namespace Bref\Websocket;

use AsyncAws\Core\AbstractApi;
use AsyncAws\Core\Request;
use AsyncAws\Core\Response;
use AsyncAws\Core\Stream\StreamFactory;

/**
 * This class sole purpose it to act as a proxy for communicating with the ApiGateway API.
 * Because async-aws does not provide a native websocket client we've had to create our own
 * client using the same base class as all other async-aws clients.
 *
 * In time if async-aws provide a websocket client this will be replaced entirely.
 *
 * Exceptions thrown should in theory be the same as they're all coming from async-aws.
 *
 * @internal
 */
final class WebsocketClient extends AbstractApi
{
    public function request(string $method, string $url, ?string $body = null): Response
    {
        return $this->getResponse(new Request($method, $url, [], [], StreamFactory::create($body)));
    }

    protected function getEndpointMetadata(?string $region): array
    {
        return [
            'endpoint' => $this->getConfiguration()->get('endpoint'),
            'signRegion' => $region ?? $this->getConfiguration()->get('region'),
            'signService' => 'execute-api',
            'signVersions' => ['v4'],
        ];
    }
}
