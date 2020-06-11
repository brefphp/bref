<?php declare(strict_types=1);

namespace Bref\Event\DynamoDb;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;

/**
 * Represents a Lambda event when Lambda is invoked by DynamoDB Streams.
 */
final class DynamoDbEvent implements LambdaEvent
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
            throw new InvalidLambdaEvent('DynamoDB', $event);
        }

        $this->event = $event;
    }

    /**
     * @return DynamoDbRecord[]
     */
    public function getRecords(): array
    {
        return array_map(
            function ($record): DynamoDbRecord {
                try {
                    return new DynamoDbRecord($record);
                } catch (\InvalidArgumentException $e) {
                    throw new InvalidLambdaEvent('DynamoDb', $this->event);
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
