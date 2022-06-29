<?php declare(strict_types=1);

namespace Bref\Event\Kafka;

use Bref\Context\Context;
use Bref\Event\Handler;

abstract class KafkaHandler implements Handler
{
    abstract public function handleKafka(KafkaEvent $event, Context $context): void;

    /** {@inheritDoc} */
    final public function handle($event, Context $context)
    {
        $this->handleKafka(new KafkaEvent($event), $context);
    }
}
