<?php declare(strict_types=1);

namespace Bref\Event\Kinesis;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;

final class KinesisEvent implements LambdaEvent
{
    /** @var array */
    private $event;

    /**
     * @param mixed $event
     * @throws InvalidLambdaEvent
     */
    public function __construct($event)
    {
        if (! is_array($event) || ! isset($event['Records'])) {
            throw new InvalidLambdaEvent('Kinesis', $event);
        }

        $this->event = $event;
    }

    /**
     * @return KinesisRecord[]
     */
    public function getRecords(): array
    {
        return array_map(
            function ($record): KinesisRecord {
                try {
                    return new KinesisRecord($record);
                } catch (\InvalidArgumentException $e) {
                    throw new InvalidLambdaEvent('Kinesis', $this->event);
                }
            },
            $this->event['Records']
        );
    }

    public function toArray(): array
    {
        return $this->event;
    }
}
