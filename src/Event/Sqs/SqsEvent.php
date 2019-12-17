<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use Bref\Event\InvalidLambdaEvent;
use InvalidArgumentException;

final class SqsEvent
{
    /** @var array */
    private $event;

    public function __construct($event)
    {
        if (! is_array($event) || ! isset($event['Records'])) {
            throw new InvalidLambdaEvent('SQS', $event);
        }
        $this->event = $event;
    }

    /**
     * @return SqsRecord[]
     */
    public function getRecords(): array
    {
        return array_map(function ($record): SqsRecord {
            try {
                return new SqsRecord($record);
            } catch (InvalidArgumentException $e) {
                throw new InvalidLambdaEvent('SQS', $this->event);
            }
        }, $this->event['Records']);
    }

    /**
     * Returns the event original data as an array.
     *
     * Use this method if you want to access data that is not returned by a method in this class.
     */
    public function toArray(): array
    {
        return $this->event;
    }
}
