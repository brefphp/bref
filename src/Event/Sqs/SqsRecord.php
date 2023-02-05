<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

final class SqsRecord
{
    private string $messageId;
    private string $body;
    private array $messageAttributes;
    private int $approximateReceiveCount;
    private string $receiptHandle;
    private array $record;

    public function __construct(
        string $messageId,
        string $body,
        array $messageAttributes,
        int $approximateReceiveCount,
        string $receiptHandle,
        array $rawRecord,
    ) {
        $this->messageId = $messageId;
        $this->body = $body;
        $this->messageAttributes = $messageAttributes;
        $this->approximateReceiveCount = $approximateReceiveCount;
        $this->receiptHandle = $receiptHandle;
        $this->record = $rawRecord;
    }

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    /**
     * Returns the body of the SQS message.
     * The body is data that was sent to SQS by the publisher of the message.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Message attributes are custom attributes sent with the body, by the publisher of the message.
     */
    public function getMessageAttributes(): array
    {
        return $this->messageAttributes;
    }

    /**
     * Returns the number of times a message has been received from the queue but not deleted.
     */
    public function getApproximateReceiveCount(): int
    {
        return $this->approximateReceiveCount;
    }

    /**
     * Returns the receipt handle, the unique identifier for a specific instance of receiving a message.
     */
    public function getReceiptHandle(): string
    {
        return $this->receiptHandle;
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
