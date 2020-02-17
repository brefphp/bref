<?php declare(strict_types=1);

namespace Bref\Event\DynamoDb;

final class DynamoDbRecord
{
    /** @var array */
    private $record;

    /**
     * DynamoDbRecord constructor.
     * @param $record
     */
    public function __construct($record)
    {
        if (!is_array($record) || !isset($record['eventSource']) || $record['eventSource'] !== 'aws:dynamodb') {
            throw new \InvalidArgumentException('Event source must come from DynamoDB');
        }

        $this->record = $record;
    }

    public function getKeys()
    {
        return $this->record['dynamodb']['Keys'];
    }

    public function getNewImage()
    {
        return $this->record['dynamodb']['NewImage'];
    }

    public function getOldImage()
    {
        return $this->record['dynamodb']['OldImage'] ?? null;
    }

    public function getSequenceNumber()
    {
        return $this->record['dynamodb']['SequenceNumber'];
    }

    public function getSizeBytes()
    {
        return $this->record['dynamodb']['SizeBytes'];
    }

    public function getStreamViewType()
    {
        return $this->record['dynamodb']['StreamViewType'];
    }
}
