<?php declare(strict_types=1);

namespace Bref\Event\DynamoDb;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;
use InvalidArgumentException;

/**
 * Represents a Lambda event when Lambda is invoked by DynamoDB Streams.
 *
 * @final
 */
class DynamoDbEvent implements LambdaEvent
{
    private array $event;

    /**
     * @throws InvalidLambdaEvent
     */
    public function __construct(mixed $event)
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
                } catch (InvalidArgumentException) {
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
