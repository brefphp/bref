<?php declare(strict_types=1);

namespace Bref\Event\DynamoDb;

/**
 * @see https://docs.aws.amazon.com/amazondynamodb/latest/APIReference/API_StreamSpecification.html
 */

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
     * @return mixed
     */
    public function getKeys()
    {
        return $this->record['dynamodb']['Keys'];
    }

    /**
     * @return mixed|null
     */
    public function getNewImage()
    {
        return $this->record['dynamodb']['NewImage'] ?? null;
    }

    /**
     * @return mixed|null
     */
    public function getOldImage()
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

    public function getStreamViewType(): string
    {
        return $this->record['dynamodb']['StreamViewType'];
    }
}
