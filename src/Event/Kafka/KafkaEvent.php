<?php declare(strict_types=1);

namespace Bref\Event\Kafka;

use Bref\Event;
use Bref\Event\InvalidLambdaEvent;
use InvalidArgumentException;

final class KafkaEvent implements Event\LambdaEvent
{
    /** @var array */
    private $event;

    /**
     * Represents a Lambda event when Lambda is invoked by Kafka.
     *
     * @param mixed $event
     */
    public function __construct($event)
    {
        if (! is_array($event) || ! isset($event['records'])) {
            throw new Event\InvalidLambdaEvent('Kafka', $event);
        }

        $this->event = $event;
    }

    /**
     * @return KafkaRecord[]
     */
    public function getRecords(): array
    {
        return array_map(function ($record): KafkaRecord {
            try {
                return new KafkaRecord($record);
            } catch (InvalidArgumentException $e) {
                throw new InvalidLambdaEvent('Kafka', $this->event);
            }
        }, array_merge(...array_values($this->event['records'])));
    }

    public function toArray(): array
    {
        return $this->event;
    }
}
