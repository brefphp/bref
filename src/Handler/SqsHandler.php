<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;

interface SqsHandler
{
    public function handleSqs(SqsEvent $event, Context $context): void;
}
