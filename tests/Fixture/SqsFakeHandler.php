<?php declare(strict_types=1);

namespace Bref\Test\Fixture;

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsHandler;

class SqsFakeHandler extends SqsHandler
{
    public function handleSqs(SqsEvent $event, Context $context): void
    {
        foreach ($event->getRecords() as $record) {
            $body = json_decode($record->getBody(), true);

            $isEven = $body['count'] % 2 === 0;

            if ($isEven) {
                $this->markAsFailed($record);
            }
        }
    }
}
