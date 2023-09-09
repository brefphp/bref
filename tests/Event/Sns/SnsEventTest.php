<?php declare(strict_types=1);

namespace Bref\Test\Event\Sns;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\Sns\SnsEvent;
use Generator;
use LogicException;
use PHPUnit\Framework\TestCase;

class SnsEventTest extends TestCase
{
    public function test canonical case()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/sns.json'), true);
        $event = new SnsEvent($event);

        $record = $event->getRecords()[0];
        $this->assertSame('95df01b4-ee98-5cb9-9903-4c221d41eb5e', $record->getMessageId());
        $this->assertSame('Hello from SNS!', $record->getMessage());
        $this->assertSame('arn:aws:sns:us-east-2:123456789012:sns-lambda:21be56ed-a058-49f5-8c98-aedd2564c486', $record->getEventSubscriptionArn());
        $this->assertSame('TestInvoke', $record->getSubject());
        $this->assertSame('arn:aws:sns:us-east-2:123456789012:sns-lambda', $record->getTopicArn());
        $this->assertSame('2019-01-02T12:45:07+00:00', $record->getTimestamp()->format(DATE_ATOM));

        $this->assertNotEmpty($record->getMessageAttributes());
        $attributes = $record->getMessageAttributes();
        $this->assertSame('String', $attributes['Test']->getType());
        $this->assertSame('TestString', $attributes['Test']->getValue());
        $this->assertSame('Binary', $attributes['TestBinary']->getType());
        $this->assertSame('TestBinary', $attributes['TestBinary']->getValue());
        $this->assertSame([
            'Type' => 'String',
            'Value' => 'TestString',
        ], $attributes['Test']->toArray());
    }

    public function provideInvalidEvents(): Generator
    {
        yield [
            'exception' => InvalidLambdaEvent::class,
            'exceptionMessage' => "This handler expected to be invoked with a SNS event (check that you are using the correct Bref runtime: https://bref.sh/docs/runtimes/#bref-runtimes).\nInstead, the handler was invoked with invalid event data",
            'event' => [],
        ];

        yield [
            'exception' => LogicException::class,
            'exceptionMessage' => 'Unexpected record type "sns". Check your AWS infrastructure.',
            'event' => [
                'Records' => [
                    'EventSource' => 'aws:sqs',
                ]
            ]
        ];
    }

    /** @dataProvider provideInvalidEvents */
    public function test invalid event(string $exception, string $exceptionMessage, array $event)
    {
        $this->expectException($exception);
        $this->expectExceptionMessage($exceptionMessage);
        new SnsEvent($event);
    }
}
