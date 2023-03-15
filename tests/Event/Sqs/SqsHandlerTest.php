<?php declare(strict_types=1);

namespace Bref\Test\Event\Sqs;

use Bref\Context\Context;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsHandler;
use PHPUnit\Framework\TestCase;

class SqsHandlerTest extends TestCase
{
    public function test partial failure()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/handler.json'), true, 512, JSON_THROW_ON_ERROR);

        // Fails half the messages
        $handler = new class extends SqsHandler {
            public function handleSqs(SqsEvent $event, Context $context): void
            {
                foreach ($event->getRecords() as $record) {
                    $body = json_decode($record->getBody(), true);

                    $isEven = $body['count'] % 2 === 0;
                    if ($isEven) {
                        $this->markAsFailed($record);
                    }
                }
            }
        };

        $result = $handler->handle($event, Context::fake());

        self::assertSame($result, [
            'batchItemFailures' => [
                ['itemIdentifier' => '2'],
                ['itemIdentifier' => '4'],
            ],
        ]);
    }

    /**
     * @see https://github.com/brefphp/bref/issues/1461
     */
    public function test response when no failure()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/handler.json'), true, 512, JSON_THROW_ON_ERROR);

        $handler = new class extends SqsHandler {
            public function handleSqs(SqsEvent $event, Context $context): void
            {
                // success (does not call $this->markAsFailed())
            }
        };

        $result = $handler->handle($event, Context::fake());

        // The response should be null, not an empty array
        self::assertNull($result);
    }
}
