<?php declare(strict_types=1);

namespace Bref\Websocket;

use DateTimeImmutable;

class WebsocketClientStatus
{
    /** @var string */
    private $sourceIp;

    /** @var string */
    private $userAgent;

    /** @var DateTimeImmutable */
    private $connectedAt;

    /** @var DateTimeImmutable */
    private $lastActiveAt;

    public function __construct(array $input)
    {
        $this->sourceIp = $input['identity']['sourceIp'];
        $this->userAgent = $input['identity']['userAgent'];
        $this->connectedAt = new DateTimeImmutable($input['connectedAt']);
        $this->lastActiveAt = new DateTimeImmutable($input['lastActiveAt']);
    }

    public function getSourceIp(): string
    {
        return $this->sourceIp;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getConnectedAt(): DateTimeImmutable
    {
        return $this->connectedAt;
    }

    public function getLastActiveAt(): DateTimeImmutable
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
