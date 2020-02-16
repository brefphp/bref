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

        // Act
        $event = new DynamoDbEvent($event);
        $records = $event->getRecords()[0];

        // Assert
        $this->assertSame(['Id' => ['N' => '101']], $records->getKeys());
    }
}
