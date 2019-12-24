<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;

/**
 * Handles SQS events.
 */
interface SqsHandler
{
    public function handle(SqsEvent $event, Context $context): void;
}
