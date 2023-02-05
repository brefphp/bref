<?php declare(strict_types=1);

namespace Bref\Test\Event\Sqs;

use Bref\Context\ContextBuilder;
use Bref\Test\Fixture\SqsFakeHandler;
use PHPUnit\Framework\TestCase;

class SqsEventTest extends TestCase
{
    public function test partial failure()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/handler.json'), true);
        $result = (new SqsFakeHandler)->handle($event, (new ContextBuilder)->buildContext());

        self::assertSame($result, [
            'batchItemFailures' => [
                ['itemIdentifier' => '2'],
                ['itemIdentifier' => '4'],
            ],
        ]);
    }
}
