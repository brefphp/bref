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

    /**
     * @return mixed
     */
    public function getKeys()
    {
        return $this->record['dynamodb']['Keys'];
    }

    /**
     * @return mixed
     */
    public function getNewImage()
    {
        return $this->record['dynamodb']['NewImage'];
    }

    /**
     * @return mixed|null
     */
    public function getOldImage()
    {
        return $this->record['dynamodb']['OldImage'] ?? null;
    }

    /**
     * @return string
     */
    public function getSequenceNumber(): string
    {
        return $this->record['dynamodb']['SequenceNumber'];
    }

    /**
     * @return int
     */
    public function getSizeBytes(): int
    {
        return $this->record['dynamodb']['SizeBytes'];
    }

    /**
     * @return string
     */
    public function getStreamViewType(): string
    {
        return $this->record['dynamodb']['StreamViewType'];
    }
}
