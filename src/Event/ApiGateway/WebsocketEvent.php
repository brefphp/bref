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

    /** @var int */
    private $eventType;

    /** @var mixed */
    private $body;

    /** @var string */
    private $connectionId;

    /** Event types */
    public const EVENT_TYPE_CONNECT = 0;
    public const EVENT_TYPE_DISCONNECT = 1;
    public const EVENT_TYPE_MESSAGE = 2;

    /**
     * @param mixed $event
     * @throws InvalidLambdaEvent
     */
    public function __construct($event)
    {
        if (
            !is_array($event) ||
            !isset($event['requestContext']['routeKey']) ||
            !isset($event['requestContext']['eventType']) ||
            !isset($event['requestContext']['connectionId']) ||
            !in_array(
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

        $this->connectionId = $event['requestContext']['connectionId'];
        $this->routeKey = $event['requestContext']['routeKey'];
        $this->event = $event;

        switch ($event['requestContext']['eventType']) {
            case 'CONNECT':
                $this->eventType = self::EVENT_TYPE_CONNECT;
                break;

            case 'DISCONNECT':
                $this->eventType = self::EVENT_TYPE_DISCONNECT;
                break;

            case 'MESSAGE':
                $this->eventType = self::EVENT_TYPE_MESSAGE;
                break;
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

    public function getEventType(): int
    {
        return $this->eventType;
    }

    public function getBody()
    {
        if (isset($this->body)) {
            return $this->body;
        }

        return null;
    }

    public function getConnectionId()
    {
        return $this->connectionId;
    }
}
