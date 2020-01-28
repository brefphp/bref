<?php declare(strict_types=1);

namespace Bref\Test\Event\Sns;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\Sns\SnsEvent;
use PHPUnit\Framework\TestCase;

class SnsEventTest extends TestCase
{
    /**
     * The date format was broken before PHP 7.3, see https://3v4l.org/77nYs
     * I don't want to add hacks that I don't understand to support PHP 7.2, I'd rather
     * encourage people to upgrade to a maintained version :)
     *
     * @requires PHP 7.3
     */
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

    public function test invalid event()
    {
        $this->expectException(InvalidLambdaEvent::class);
        $this->expectExceptionMessage('This handler expected to be invoked with a SNS event. Instead, the handler was invoked with invalid event data');
        new SnsEvent([]);
    }
}
