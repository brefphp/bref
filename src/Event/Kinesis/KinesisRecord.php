<?php declare(strict_types=1);

namespace Bref\Event\Kinesis;

use DateTimeImmutable;

final class KinesisRecord
{
    /** @var array */
    private $record;

    /**
     * @param mixed $record
     */
    public function __construct($record)
    {
        if (! is_array($record) || ! isset($record['eventSource']) || $record['eventSource'] !== 'aws:kinesis') {
            throw new \InvalidArgumentException('Event source must come from Kinesis');
        }

        $this->record = $record;
    }

    public function getApproximateArrivalTime(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('U.u', (string) $this->record['kinesis']['approximateArrivalTimestamp']);
    }

    public function getData(): array
    {
        return json_decode(base64_decode($this->getRawData()), true);
    }

    public function getEventName(): string
    {
        return $this->record['eventName'];
    }

    public function getPartitionKey(): string
    {
        return $this->record['kinesis']['partitionKey'];
    }

    public function getRawData(): string
    {
        return $this->record['kinesis']['data'];
    }

    public function getSequenceNumber(): string
    {
        return $this->record['kinesis']['sequenceNumber'];
    }
}
