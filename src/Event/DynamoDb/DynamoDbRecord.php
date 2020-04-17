<?php declare(strict_types=1);

namespace Bref\Event\DynamoDb;

final class DynamoDbRecord
{
    /** @var array */
    private $record;

    /**
     * @param mixed $record
     */
    public function __construct($record)
    {
        if (! is_array($record) || ! isset($record['eventSource']) || $record['eventSource'] !== 'aws:dynamodb') {
            throw new \InvalidArgumentException('Event source must come from DynamoDB');
        }

        $this->record = $record;
    }

    public function getEventName(): string
    {
        return $this->record['eventName'];
    }

    /**
     * Returns the key attributes of the modified item.
     */
    public function getKeys(): array
    {
        return $this->record['dynamodb']['Keys'];
    }

    /**
     * Returns the new version of the DynamoDB item.
     *
     * Warning: this can be null depending on the `StreamViewType`.
     *
     * @see getStreamViewType()
     * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_StreamSpecification.html
     */
    public function getNewImage(): ?array
    {
        return $this->record['dynamodb']['NewImage'] ?? null;
    }

    /**
     * Returns the old version of the DynamoDB item.
     *
     * Warning: this can be null depending on the `StreamViewType`.
     *
     * @see getStreamViewType()
     * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_StreamSpecification.html
     */
    public function getOldImage(): ?array
    {
        return $this->record['dynamodb']['OldImage'] ?? null;
    }

    public function getSequenceNumber(): string
    {
        return $this->record['dynamodb']['SequenceNumber'];
    }

    public function getSizeBytes(): int
    {
        return $this->record['dynamodb']['SizeBytes'];
    }

    /**
     * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_StreamSpecification.html
     */
    public function getStreamViewType(): string
    {
        return $this->record['dynamodb']['StreamViewType'];
    }
}
