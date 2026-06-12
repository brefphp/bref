<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use InvalidArgumentException;

/**
 * @final
 */
class SqsRecord
{
    private array $record;

    public function __construct(mixed $record)
    {
        if (! is_array($record) || ! isset($record['eventSource']) || $record['eventSource'] !== 'aws:sqs') {
            throw new InvalidArgumentException;
        }
        $this->record = $record;
    }

    public function getMessageId(): string
    {
        return $this->record['messageId'];
    }

    /**
     * Returns the body of the SQS message.
     * The body is data that was sent to SQS by the publisher of the message.
     */
    public function getBody(): string
    {
        return $this->record['body'];
    }

    /**
     * Message attributes are custom attributes sent with the body, by the publisher of the message.
     */
    public function getMessageAttributes(): array
    {
        return $this->record['messageAttributes'];
    }

    /**
     * Returns the number of times a message has been received from the queue but not deleted.
     */
    public function getApproximateReceiveCount(): int
    {
        return (int) $this->record['attributes']['ApproximateReceiveCount'];
    }

    /**
     * Returns the receipt handle, the unique identifier for a specific instance of receiving a message.
     */
    public function getReceiptHandle(): string
    {
        return $this->record['receiptHandle'];
    }

    /**
     * Returns the name of the SQS queue that contains the message.
     * Queue naming constraints: https://docs.aws.amazon.com/AWSSimpleQueueService/latest/SQSDeveloperGuide/quotas-queues.html
     */
    public function getQueueName(): string
    {
        $parts = explode(':', $this->record['eventSourceARN']);

        return $parts[count($parts) - 1];
    }

    /**
     * Returns the full URL of the SQS queue that contains the message.
     * Reconstructed from the event source ARN, so it can be used with the SQS API (e.g. DeleteMessage, SendMessage)
     * without an extra API call or the need for the user to configure the account ID.
     */
    public function getQueueUrl(): string
    {
        // ARN format: arn:<partition>:sqs:<region>:<account>:<queue-name>
        [, $partition, , $region, $account, $queueName] = explode(':', $this->record['eventSourceARN']);

        // Each AWS partition has its own DNS suffix. Default to the standard suffix for unknown partitions.
        $tld = match ($partition) {
            'aws-cn' => 'amazonaws.com.cn',
            'aws-eusc' => 'amazonaws.eu',
            default => 'amazonaws.com',
        // @phpcs:disable
        };

        return "https://sqs.{$region}.{$tld}/{$account}/{$queueName}";
    }

    /**
     * Returns the record original data as an array.
     *
     * Use this method if you want to access data that is not returned by a method in this class.
     */
    public function toArray(): array
    {
        return $this->record;
    }
}
