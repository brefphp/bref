<?php

namespace Bref\ApiGateway;

class WebsocketClientStatus
{
    /** @var string */
    private $sourceIp;

    /** @var string */
    private $userAgent;

    /** @var string */
    private $connectedAt;

    /** @var string */
    private $lastActiveAt;

    public function __construct(array $input)
    {
        $this->sourceIp = $input['identity']['sourceIp'];
        $this->userAgent = $input['identity']['userAgent'];
        $this->connectedAt = $input['connectedAt'];
        $this->lastActiveAt = $input['lastActiveAt'];
    }

    public function getSourceIp(): string
    {
        return $this->sourceIp;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getConnectedAt(): string
    {
        return $this->connectedAt;
    }

    public function getLastActiveAt(): string
    {
        return $this->lastActiveAt;
    }

    public function toArray(): array
    {
        return [
            'sourceIp' => $this->sourceIp,
            'userAgent' => $this->userAgent,
            'connectedAt' => $this->connectedAt,
            'lastActiveAt' => $this->lastActiveAt,
        ];
    }
}