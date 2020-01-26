<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * Handles SQS events.
 */
abstract class SqsHandler implements Handler
{
    abstract public function handleSqs(SqsEvent $event, Context $context): void;

    public function handle($event, Context $context): void
    {
        $this->handleSqs(new SqsEvent($event), $context);
    }
}
