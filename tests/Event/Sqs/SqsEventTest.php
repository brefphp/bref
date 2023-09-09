<?php declare(strict_types=1);

namespace Bref\Test\Event\Sqs;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\Sqs\SqsEvent;
use Generator;
use LogicException;
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
        $this->assertSame('AQEBwJnKyrHigUMZj6rYigCgxlaS3SLy0a...', $record->getReceiptHandle());

        $record = $event->getRecords()[1];
        $this->assertSame('2e1424d4-f796-459a-8184-9c92662be6da', $record->getMessageId());
        $this->assertSame('Test message.', $record->getBody());
        $this->assertSame([], $record->getMessageAttributes());
        $this->assertSame(4, $record->getApproximateReceiveCount());
        $this->assertSame('AQEBzWwaftRI0KuVm4tP+/7q1rGgNqicHq...', $record->getReceiptHandle());
    }

    public function provideInvalidEvents(): Generator
    {
        yield [
            'exception' => InvalidLambdaEvent::class,
            'exceptionMessage' => "This handler expected to be invoked with a SQS event (check that you are using the correct Bref runtime: https://bref.sh/docs/runtimes/#bref-runtimes).\nInstead, the handler was invoked with invalid event data",
            'event' => [],
        ];

        yield [
            'exception' => LogicException::class,
            'exceptionMessage' => 'Unexpected record type "sns". Check your AWS infrastructure.',
            'event' => [
                'Records' => [
                    'eventSource' => 'aws:sns',
                ]
            ]
        ];
    }

    /** @dataProvider provideInvalidEvents */
    public function test invalid event(string $exception, string $exceptionMessage, array $event)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        new SqsEvent($event);
    }
}
