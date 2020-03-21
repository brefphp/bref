<?php declare(strict_types=1);

namespace Bref\Test\Event\EventBridge;

use Bref\Event\EventBridge\EventBridgeEvent;
use Bref\Event\InvalidLambdaEvent;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class EventBridgeEventTest extends TestCase
{
    public function test canonical case()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/eventbridge.json'), true);
        $event = new EventBridgeEvent($json);

        $this->assertSame('53dc4d37-cffa-4f76-80c9-8b7d4a4d2eaa', $event->getId());
        $this->assertSame('0', $event->getVersion());
        $this->assertSame('us-east-1', $event->getAwsRegion());
        $this->assertSame('123456789012', $event->getAwsAccountId());
        $this->assertSame('myapp', $event->getSource());
        $this->assertEquals(new DateTimeImmutable('2015-10-08T16:53:06Z'), $event->getTimestamp());
        $this->assertSame('Example event', $event->getDetailType());
        $this->assertSame([
            'file' => 'streaming/video.mkv',
        ], $event->getDetail());
    }

    public function test event with empty details()
    {
        $json = json_decode(file_get_contents(__DIR__ . '/eventbridge-details-empty.json'), true);
        $event = new EventBridgeEvent($json);

        $this->assertSame('53dc4d37-cffa-4f76-80c9-8b7d4a4d2eaa', $event->getId());
        $this->assertSame('0', $event->getVersion());
        $this->assertSame('us-east-1', $event->getAwsRegion());
        $this->assertSame('123456789012', $event->getAwsAccountId());
        $this->assertSame('aws.events', $event->getSource());
        $this->assertEquals(new DateTimeImmutable('2015-10-08T16:53:06Z'), $event->getTimestamp());
        $this->assertSame('Example event', $event->getDetailType());
        $this->assertSame([], $event->getDetail());
    }

    public function test invalid event()
    {
        $this->expectException(InvalidLambdaEvent::class);
        $this->expectExceptionMessage('This handler expected to be invoked with a EventBridge event. Instead, the handler was invoked with invalid event data');
        new EventBridgeEvent([]);
    }
}
