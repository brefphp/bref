<?php declare(strict_types=1);

namespace Bref\Test\Event\Kinesis;

use Bref\Event\Kinesis\KinesisEvent;
use PHPUnit\Framework\TestCase;

class KinesisEventTest extends TestCase
{
    public function test_event()
    {
        // Arrange
        $rawEvent = json_decode(file_get_contents(__DIR__ . '/kinesis.json'), true);

        $data = [
            'bref' => 1,
            'data' => 1,
        ];

        $date = new \DateTime('2020-04-28T05:20:20.453000Z');

        // Act
        $event = new KinesisEvent($rawEvent);
        $record = $event->getRecords()[0];

        // Assert
        $this->assertSame('1', $record->getSequenceNumber());
        $this->assertSame('bref-1', $record->getPartitionKey());
        $this->assertSame('eyJicmVmIjoxLCJkYXRhIjoxfQ==', $record->getRawData());
        $this->assertSame($data, $record->getData());
        $this->assertEqualsWithDelta($date->getTimestamp(), $record->getApproximateArrivalTime()->getTimestamp(), 0);
    }
}
