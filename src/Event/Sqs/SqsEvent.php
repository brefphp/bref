<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;
use InvalidArgumentException;

/**
 * Represents a Lambda event when Lambda is invoked by SQS.
 *
 * @final
 */
class SqsEvent implements LambdaEvent
{
    private array $event;

    public function __construct(mixed $event)
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
            } catch (InvalidArgumentException) {
                throw new InvalidLambdaEvent('SQS', $this->event);
            }
        }, $this->event['Records']);
    }

    public function toArray(): array
    {
        return $this->event;
    }
}
