<?php declare(strict_types=1);

namespace Bref\ApiGateway;

use AsyncAws\Core\AbstractApi;
use AsyncAws\Core\Request;
use AsyncAws\Core\Response;

class WebsocketClient extends AbstractApi
{
    public function process(Request $request): Response
    {
        return $this->getResponse($request);
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
