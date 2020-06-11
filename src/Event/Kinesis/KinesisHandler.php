<?php declare(strict_types=1);

namespace Bref\Event\Kinesis;

use Bref\Context\Context;
use Bref\Event\Handler;

abstract class KinesisHandler implements Handler
{
    abstract public function handleKinesis(KinesisEvent $event, Context $context): void;

    /** {@inheritDoc} */
    public function handle($event, Context $context): void
    {
        $this->handleKinesis(new KinesisEvent($event), $context);
    }
}
