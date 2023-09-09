<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use InvalidArgumentException;
use LogicException;

/**
 * @final
 */
class SqsRecord
{
    private array $record;

    public function __construct(mixed $record)
    {
        if (! is_array($record) || ! isset($record['eventSource'])) {
            throw new InvalidArgumentException;
        }

        if ($record['eventSource'] === 'aws:sns') {
            throw new LogicException('Unexpected record type "sns". Check your AWS infrastructure.');
        }

        if ($record['eventSource'] !== 'aws:sqs') {
            throw new InvalidArgumentException;
        }

        $this->record = $record;
    }

    public function getMessageId(): string
    {
        return $this->record['messageId'];
    }

    /**
     * Returns the body of the SQS message.
     * The body is data that was sent to SQS by the publisher of the message.
     */
    public function getBody(): string
    {
        return $this->record['body'];
    }

    /**
     * Message attributes are custom attributes sent with the body, by the publisher of the message.
     */
    public function getMessageAttributes(): array
    {
        return $this->record['messageAttributes'];
    }

    /**
     * Returns the number of times a message has been received from the queue but not deleted.
     */
    public function getApproximateReceiveCount(): int
    {
        return (int) $this->record['attributes']['ApproximateReceiveCount'];
    }

    /**
     * Returns the receipt handle, the unique identifier for a specific instance of receiving a message.
     */
    public function getReceiptHandle(): string
    {
        return $this->record['receiptHandle'];
    }

    /**
     * Returns the record original data as an array.
     *
     * Use this method if you want to access data that is not returned by a method in this class.
     */
    public function toArray(): array
    {
        return $this->record;
    }
}
