<?php declare(strict_types=1);

namespace Bref\Event\Sns;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * Handles SNS events.
 */
abstract class SnsHandler implements Handler
{
    abstract public function handleSns(SnsEvent $event, Context $context): void;

    /** {@inheritDoc} */
    public function handle($event, Context $context): void
    {
        $this->handleSns(new SnsEvent($event), $context);
    }
}
