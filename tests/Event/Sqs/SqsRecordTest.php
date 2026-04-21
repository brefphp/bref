<?php declare(strict_types=1);

namespace Bref\Test\Event\Sqs;

use Bref\Event\Sqs\SqsRecord;
use PHPUnit\Framework\TestCase;

class SqsRecordTest extends TestCase
{
    public function test_it_can_get_queue_name_from_sqs_event()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/sqs.json'), true);

        $sqsRecord = new SqsRecord($event['Records'][0]);

        $this->assertSame($sqsRecord->getQueueName(), 'my-queue');
    }

    public function test_it_can_get_queue_url_from_sqs_event()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/sqs.json'), true);

        $sqsRecord = new SqsRecord($event['Records'][0]);

        $this->assertSame(
            'https://sqs.us-east-2.amazonaws.com/123456789012/my-queue',
            $sqsRecord->getQueueUrl(),
        );
    }

    public function test_it_builds_queue_url_for_china_partition()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/sqs.json'), true);
        $event['Records'][0]['eventSourceARN'] = 'arn:aws-cn:sqs:cn-north-1:123456789012:my-queue';

        $sqsRecord = new SqsRecord($event['Records'][0]);

        $this->assertSame(
            'https://sqs.cn-north-1.amazonaws.com.cn/123456789012/my-queue',
            $sqsRecord->getQueueUrl(),
        );
    }

    public function test_it_builds_queue_url_for_european_sovereign_cloud_partition()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/sqs.json'), true);
        $event['Records'][0]['eventSourceARN'] = 'arn:aws-eusc:sqs:eusc-de-east-1:123456789012:my-queue';

        $sqsRecord = new SqsRecord($event['Records'][0]);

        $this->assertSame(
            'https://sqs.eusc-de-east-1.amazonaws.eu/123456789012/my-queue',
            $sqsRecord->getQueueUrl(),
        );
    }
}
