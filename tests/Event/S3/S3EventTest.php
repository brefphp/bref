<?php declare(strict_types=1);

namespace Bref\Test\Event\S3;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\S3\S3Event;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class S3EventTest extends TestCase
{
    public function test canonical case()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/s3.json'), true);
        $event = new S3Event($event);

        $record = $event->getRecords()[0];
        $this->assertSame('us-east-1', $record->getAwsRegion());
        $this->assertSame('ObjectCreated:Put', $record->getEventName());
        $this->assertEquals(new DateTimeImmutable('2019-08-05T15:30+0000'), $record->getEventTime());
        $this->assertSame('mybucket', $record->getBucket()->getName());
        $this->assertSame('arn:aws:s3:::mybucket', $record->getBucket()->getArn());
        $this->assertSame('folder/hello.jpg', $record->getObject()->getKey());
        $this->assertSame(1024, $record->getObject()->getSize());
        $this->assertSame('096fKKXTRTtl3on89fVO.nfljtsv6qko', $record->getObject()->getVersionId());
    }

    public function test invalid event()
    {
        $this->expectException(InvalidLambdaEvent::class);
        $this->expectExceptionMessage('This handler expected to be invoked with a S3 event. Instead, the handler was invoked with invalid event data');
        new S3Event([]);
    }
}
