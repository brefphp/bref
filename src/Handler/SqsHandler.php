<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;

/**
 * Handles SQS events. This should be used with SqsWrapper.
 */
interface SqsHandler
{
    public function __invoke(SqsEvent $event, Context $context): void;
}
