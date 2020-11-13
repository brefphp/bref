<?php declare(strict_types=1);

namespace Bref\Event\ApiGateway;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Event\Http\HttpResponse;

/**
 * Handles ApiGateway events.
 */
abstract class WebsocketHandler implements Handler
{
    abstract public function handleWebsocket(WebsocketEvent $event, Context $context): HttpResponse;

    /** {@inheritDoc} */
    public function handle($event, Context $context)
    {
        return $this->handleWebsocket(new WebsocketEvent($event), $context)->toApiGatewayFormat();
    }
}
