<?php declare(strict_types=1);

namespace Bref\Event\Sns;

use DateTimeImmutable;
use InvalidArgumentException;
use LogicException;

/**
 * Represents a SNS message record.
 *
 * For more information about each field, see https://docs.aws.amazon.com/sns/latest/api/API_Publish.html
 *
 * @final
 */
class SnsRecord
{
    private array $record;

    public function __construct(mixed $record)
    {
        if (! is_array($record) || ! isset($record['EventSource'])) {
            throw new InvalidArgumentException;
        }

        if ($record['EventSource'] === 'aws:sqs') {
            throw new LogicException('Unexpected record type "sqs". Check your AWS infrastructure.');
        }

        if ($record['EventSource'] !== 'aws:sns') {
            throw new InvalidArgumentException;
        }

        $this->record = $record;
    }

    public function getEventSubscriptionArn(): string
    {
        return $this->record['EventSubscriptionArn'];
    }

    public function getMessageId(): string
    {
        return $this->record['Sns']['MessageId'];
    }

    /**
     * Optional parameter to be used as the "Subject" line when the message is delivered to email endpoints. This field will also be included, if present, in the standard JSON messages delivered to other endpoints.
     */
    public function getSubject(): string
    {
        return $this->record['Sns']['Subject'];
    }

    /**
     * Returns the body of the SNS message.
     * The body is data that was sent to SNS by the publisher of the message.
     */
    public function getMessage(): string
    {
        return $this->record['Sns']['Message'];
    }

    /**
     * Message attributes are custom attributes sent with the message, by the publisher of the message.
     *
     * @return array<string,MessageAttribute>
     */
    public function getMessageAttributes(): array
    {
        return array_map(function (array $attribute): MessageAttribute {
            return new MessageAttribute($attribute);
        }, $this->record['Sns']['MessageAttributes']);
    }

    public function getTopicArn(): string
    {
        return $this->record['Sns']['TopicArn'];
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat(DATE_RFC3339_EXTENDED, $this->record['Sns']['Timestamp']);
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
