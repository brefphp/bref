<?php declare(strict_types=1);

namespace Bref\Event\S3;

use Bref\Context\Context;

/**
 * Handles S3 events.
 */
interface S3Handler
{
    public function handle(S3Event $event, Context $context): void;
}
