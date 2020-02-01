<?php declare(strict_types=1);

namespace Bref\Event\S3;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;
use InvalidArgumentException;

/**
 * Represents an event when Lambda is invoked by S3.
 *
 * @see https://docs.aws.amazon.com/AmazonS3/latest/dev/notification-content-structure.html
 */
final class S3Event implements LambdaEvent
{
    /** @var array */
    private $event;

    /**
     * @param mixed $event
     *
     * @internal
     */
    public function __construct($event)
    {
        if (! is_array($event) || ! isset($event['Records'])) {
            throw new InvalidLambdaEvent('S3', $event);
        }
        $this->event = $event;
    }

    /**
     * @return S3Record[]
     */
    public function getRecords(): array
    {
        return array_map(function ($record): S3Record {
            try {
                return new S3Record($record);
            } catch (InvalidArgumentException $e) {
                throw new InvalidLambdaEvent('S3', $this->event);
            }
        }, $this->event['Records']);
    }

    public function toArray(): array
    {
        return $this->event;
    }
}
