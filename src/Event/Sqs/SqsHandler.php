<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use Bref\Context\Context;

/**
 * Handles SQS events.
 */
interface SqsHandler
{
    public function handle(SqsEvent $event, Context $context): void;
}
