<?php declare(strict_types=1);

namespace Bref\Event\S3;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/notification-content-structure.html
 */
final class S3Record
{
    /** @var array */
    private $record;

    /**
     * @param mixed $record
     *
     * @internal
     */
    public function __construct($record)
    {
        if (! is_array($record) || ! isset($record['eventSource']) || $record['eventSource'] !== 'aws:s3') {
            throw new InvalidArgumentException;
        }
        $this->record = $record;
    }

    /**
     * Returns the bucket that triggered the lambda.
     */
    public function getBucket(): Bucket
    {
        $bucket = $this->record['s3']['bucket'];
        return new Bucket($bucket['name'], $bucket['arn']);
    }

    /**
     * Returns the object that triggered the lambda.
     */
    public function getObject(): BucketObject
    {
        $bucket = $this->record['s3']['object'];
        return new BucketObject($bucket['key'], $bucket['size'] ?? 0, $bucket['versionId'] ?? null);
    }

    public function getEventTime(): DateTimeImmutable
    {
        return new DateTimeImmutable($this->record['eventTime']);
    }

    public function getEventName(): string
    {
        return $this->record['eventName'];
    }

    public function getAwsRegion(): string
    {
        return $this->record['awsRegion'];
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
