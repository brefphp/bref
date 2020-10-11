<?php declare(strict_types=1);

namespace Bref\Event\ApiGateway;

use Bref\Context\Context;
use Bref\Event\Handler;
use RuntimeException;

/**
 * Handles ApiGateway events.
 */
abstract class WebsocketHandler implements Handler
{
    /**
     * @return WebsocketResponse|int
     */
    abstract public function handleWebsocket(WebsocketEvent $event, Context $context);

    /** {@inheritDoc} */
    public function handle($event, Context $context)
    {
        $response = $this->handleWebsocket(new WebsocketEvent($event), $context);

        if (is_int($response)) {
            $response = new WebsocketResponse($response);
        }

        if (! ($response instanceof WebsocketResponse)) {
            throw new RuntimeException('handleWebsocket response has to be of type: WebsocketResponse|int');
        }

        return $response->toApiGatewayFormat();
    }
}
