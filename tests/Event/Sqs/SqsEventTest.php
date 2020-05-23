<?php declare(strict_types=1);

namespace Bref\Test\Event\Sqs;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\Sqs\SqsEvent;
use PHPUnit\Framework\TestCase;

class SqsEventTest extends TestCase
{
    public function test canonical case()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/sqs.json'), true);
        $event = new SqsEvent($event);

        $record = $event->getRecords()[0];
        $this->assertSame('059f36b4-87a3-44ab-83d2-661975830a7d', $record->getMessageId());
        $this->assertSame('Test message.', $record->getBody());
        $this->assertSame([
            'Foobar' => [
                'stringValue' => 'my value',
                'stringListValues' => [],
                'binaryListValues' => [],
                'dataType' => 'String',
            ],
        ], $record->getMessageAttributes());
        $this->assertSame(1, $record->getApproximateReceiveCount());

        $record = $event->getRecords()[1];
        $this->assertSame('2e1424d4-f796-459a-8184-9c92662be6da', $record->getMessageId());
        $this->assertSame('Test message.', $record->getBody());
        $this->assertSame([], $record->getMessageAttributes());
        $this->assertSame(4, $record->getApproximateReceiveCount());
    }

    public function test invalid event()
    {
        $this->expectException(InvalidLambdaEvent::class);
        $this->expectExceptionMessage('This handler expected to be invoked with a SQS event. Instead, the handler was invoked with invalid event data');
        new SqsEvent([]);
    }
}
