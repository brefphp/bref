<?php declare(strict_types=1);

namespace Bref\Event\EventBridge;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * Handles EventBridge events.
 */
abstract class EventBridgeHandler implements Handler
{
    abstract public function handleEventBridge(EventBridgeEvent $event, Context $context): void;

    /** {@inheritDoc} */
    public function handle($event, Context $context): void
    {
        $this->handleEventBridge(new EventBridgeEvent($event), $context);
    }
}
