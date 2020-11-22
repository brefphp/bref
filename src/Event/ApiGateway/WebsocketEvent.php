<?php declare(strict_types=1);

namespace Bref\Event\ApiGateway;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;

/**
 * Represents a Lambda event when Lambda is invoked by ApiGateway websocket route.
 */
final class WebsocketEvent implements LambdaEvent
{
    /** @var array */
    private $event;

    /** @var string */
    private $routeKey;

    /** @var string|null */
    private $eventType = null;

    /** @var mixed|null */
    private $body = null;

    /** @var string */
    private $connectionId;

    /** @var string */
    private $domainName;

    /** @var string */
    private $apiId;

    /** @var string */
    private $stage;

    /**
     * @param mixed $event
     * @throws InvalidLambdaEvent
     */
    public function __construct($event)
    {
        if (
            ! is_array($event) ||
            ! isset($event['requestContext']['routeKey']) ||
            ! isset($event['requestContext']['eventType']) ||
            ! isset($event['requestContext']['connectionId']) ||
            ! isset($event['requestContext']['domainName']) ||
            ! isset($event['requestContext']['apiId']) ||
            ! isset($event['requestContext']['stage']) ||
            ! in_array(
                $event['requestContext']['eventType'],
                [
                    'CONNECT',
                    'DISCONNECT',
                    'MESSAGE',
                ],
                true
            )
        ) {
            throw new InvalidLambdaEvent('Websocket', $event);
        }

        $this->domainName = $event['requestContext']['domainName'];
        $this->connectionId = $event['requestContext']['connectionId'];
        $this->routeKey = $event['requestContext']['routeKey'];
        $this->apiId = $event['requestContext']['apiId'];
        $this->stage = $event['requestContext']['stage'];
        $this->event = $event;

        if (isset($event['requestContext']['eventType'])) {
            $this->eventType = $event['requestContext']['eventType'];
        }

        if (isset($event['body'])) {
            $this->body = $event['body'];
        }
    }

    public function toArray(): array
    {
        return $this->event;
    }

    public function getRouteKey(): string
    {
        return $this->routeKey;
    }

    public function getEventType(): ?string
    {
        return $this->eventType;
    }

    /**
     * @return mixed|null
     */
    public function getBody()
    {
        return $this->body;
    }

    public function getConnectionId(): string
    {
        return $this->connectionId;
    }

    public function getDomainName(): string
    {
        return $this->domainName;
    }

    public function getApiId(): string
    {
        return $this->apiId;
    }

    public function getStage(): string
    {
        return $this->stage;
    }

    public function getRegion(): string
    {
        [, , $region] = explode('.', $this->getDomainName(), 4);
        return $region;
    }
}
