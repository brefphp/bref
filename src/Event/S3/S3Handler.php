<?php declare(strict_types=1);

namespace Bref\Event\S3;

use Bref\Context\Context;
use Bref\Event\Handler;

/**
 * Handles S3 events.
 */
abstract class S3Handler implements Handler
{
    abstract public function handleS3(S3Event $event, Context $context): void;

    /** {@inheritDoc} */
    public function handle($event, Context $context): void
    {
        $this->handleS3(new S3Event($event), $context);
    }
}
