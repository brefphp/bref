<?php declare(strict_types=1);

namespace Bref\Test\Event\Sqs;

use Bref\Event\Sqs\SqsRecord;
use PHPUnit\Framework\TestCase;

class SqsRecordTest extends TestCase
{
    public function test_it_can_get_queue_name_from_sqs_event() {
        $event = json_decode(file_get_contents(__DIR__ . '/sqs.json'), true);
        
        $sqsRecord = new SqsRecord($event['Records'][0]);

        $this->assertSame($sqsRecord->getQueueName(), 'my-queue');
    }
}
