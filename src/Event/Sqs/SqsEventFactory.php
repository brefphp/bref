<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use Bref\Event\InvalidLambdaEvent;
use InvalidArgumentException;

final class SqsEventFactory implements SqsEventFactoryInterface
{
    public function createFromPayload(array $payload): SqsEvent
    {
        if (! isset($payload['Records'])) {
            throw new InvalidLambdaEvent('SQS', $payload);
        }

        return new SqsEvent(array_map([$this, 'createSqsRecord'], $payload['Records']), $payload);
    }

    private function createSqsRecord(array $payload): SqsRecord
    {
        if (! isset($payload['eventSource']) || $payload['eventSource'] !== 'aws:sqs') {
            throw new InvalidArgumentException;
        }
        return new SqsRecord(
            $payload['messageId'],
            $payload['body'],
            $payload['messageAttributes'],
            (int) $payload['attributes']['ApproximateReceiveCount'],
            $payload['receiptHandle'],
            $payload,
        );
    }
}
