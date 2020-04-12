<?php declare(strict_types=1);

namespace Bref\Test\Event\DynamoDb;

use Bref\Event\DynamoDb\DynamoDbEvent;
use PHPUnit\Framework\TestCase;

class DynamoDbEventTest extends TestCase
{
    public function test_canonical_case()
    {
        // Arrange
        $event = json_decode(file_get_contents(__DIR__ . '/dynamodb.json'), true);
        $keys = ['Id' => ['N' => '101']];
        $newImage = [
            'Message' => ['S' => 'New item!'],
            'Id' => ['N' => '101'],
        ];

        // Act
        $event = new DynamoDbEvent($event);
        $record = $event->getRecords()[0];

        // Assert
        $this->assertSame($keys, $record->getKeys());
        $this->assertSame($newImage, $record->getNewImage());
        $this->assertNull($record->getOldImage());
        $this->assertSame('111', $record->getSequenceNumber());
        $this->assertSame(26, $record->getSizeBytes());
        $this->assertSame('NEW_AND_OLD_IMAGES', $record->getStreamViewType());
    }

    public function test_old_image()
    {
        // Arrange
        $event = json_decode(file_get_contents(__DIR__ . '/dynamodb.json'), true);
        $keys = ['Id' => ['N' => '101']];
        $newImage = [
            'Message' => ['S' => 'New item!'],
            'Id' => ['N' => '101'],
        ];
        $eventName = 'INSERT';

        // Act
        $event = new DynamoDbEvent($event);
        $record = $event->getRecords()[0];

        // Assert
        $this->assertSame($eventName, $record->getEventName());
        $this->assertSame($keys, $record->getKeys());
        $this->assertSame($newImage, $record->getNewImage());
        $this->assertNull($record->getOldImage());
        $this->assertSame('111', $record->getSequenceNumber());
        $this->assertSame(26, $record->getSizeBytes());
        $this->assertSame('NEW_IMAGE', $record->getStreamViewType());
    }

    public function test_new_and_old_images()
    {
        // Arrange
        $event = json_decode(file_get_contents(__DIR__ . '/dynamodb.json'), true);
        $keys = ['Id' => ['N' => '101']];
        $newImage = [
            'Message' => ['S' => 'This item has changed'],
            'Id' => ['N' => '101'],
        ];
        $oldImage = [
            'Message' => ['S' => 'New item!'],
            'Id' => ['N' => '101'],
        ];
        $eventName = 'MODIFY';

        // Act
        $event = new DynamoDbEvent($event);
        $record = $event->getRecords()[1];

        // Assert
        $this->assertSame($eventName, $record->getEventName());
        $this->assertSame($keys, $record->getKeys());
        $this->assertSame($newImage, $record->getNewImage());
        $this->assertSame($oldImage, $record->getOldImage());
        $this->assertSame('222', $record->getSequenceNumber());
        $this->assertSame(59, $record->getSizeBytes());
        $this->assertSame('NEW_AND_OLD_IMAGES', $record->getStreamViewType());
    }

    public function test_keys_only()
    {
        // Arrange
        $event = json_decode(file_get_contents(__DIR__ . '/dynamodb.json'), true);
        $keys = ['Id' => ['N' => '101']];
        $eventName = 'REMOVE';

        // Act
        $event = new DynamoDbEvent($event);
        $record = $event->getRecords()[3];

        // Assert
        $this->assertSame($eventName, $record->getEventName());
        $this->assertSame($keys, $record->getKeys());
        $this->assertNull($record->getNewImage());
        $this->assertNull($record->getOldImage());
        $this->assertSame('444', $record->getSequenceNumber());
        $this->assertSame(38, $record->getSizeBytes());
        $this->assertSame('KEYS_ONLY', $record->getStreamViewType());
    }
}
