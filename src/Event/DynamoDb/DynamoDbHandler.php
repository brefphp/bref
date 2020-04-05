<?php declare(strict_types=1);

namespace Bref\Event\DynamoDb;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * Handles DynamoDB events.
 */
abstract class DynamoDbHandler implements Handler
{
    abstract public function handleDynamoDb(DynamoDbEvent $event, Context $context): void;

    /** {@inheritDoc} */
    public function handle($event, Context $context): void
    {
        $this->handleDynamoDb(new DynamoDbEvent($event), $context);
    }
}
