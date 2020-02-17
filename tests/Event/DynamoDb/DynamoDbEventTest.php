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
        $newRecord = $event->getRecords()[0];
        $updatedRecord = $event->getRecords()[1];

        // Assert
        $this->assertSame($keys, $newRecord->getKeys());
        $this->assertSame($newImage, $newRecord->getNewImage());
        $this->assertNull($newRecord->getOldImage());
        $this->assertSame('111', $newRecord->getSequenceNumber());
        $this->assertSame(26, $newRecord->getSizeBytes());
        $this->assertSame('NEW_AND_OLD_IMAGES', $newRecord->getStreamViewType());
    }
}
