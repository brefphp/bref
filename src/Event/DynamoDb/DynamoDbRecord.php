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
            throw new \InvalidArgumentException;
        }

        $this->record = $record;
    }

    public function getKeys()
    {
        return $this->record['dynamodb']['Keys'];
    }
}
