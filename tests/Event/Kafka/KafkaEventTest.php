<?php declare(strict_types=1);

namespace Bref\Test\Event\Kafka;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\Kafka\KafkaEvent;
use PHPUnit\Framework\TestCase;

final class KafkaEventTest extends TestCase
{
    public function test canonical case()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/kafka.json'), true);
        $event = new KafkaEvent($event);

        $record = $event->getRecords()[0];
        self::assertSame('mytopic', $record->getTopic());
        self::assertSame(0, $record->getPartition());
        self::assertSame(15, $record->getOffset());
        self::assertSame(1545084650987, $record->getTimestamp());
        self::assertSame('Hello, this is a test.', $record->getValue());
        self::assertSame(
            [
                'type'           => 'core',
                'another_header' => '...',
            ],
            $record->getHeaders(),
        );
    }

    public function test invalid event()
    {
        $this->expectException(InvalidLambdaEvent::class);
        $this->expectExceptionMessage("This handler expected to be invoked with a Kafka event (check that you are using the correct Bref runtime: https://bref.sh/docs/runtimes/#bref-runtimes).\nInstead, the handler was invoked with invalid event data");
        new KafkaEvent([]);
    }
}
