<?php

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
        /** @var string $endpoint */
        $endpoint = $this->getConfiguration()->get('endpoint');

        /** @var string $region */
        $region = $region ?? $this->getConfiguration()->get('region');

        return [
            'endpoint' => $endpoint,
            'signRegion' => $region,
            'signService' => 'execute-api',
            'signVersions' => ['v4'],
        ];
    }
}