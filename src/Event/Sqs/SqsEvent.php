<?php declare(strict_types=1);

namespace Bref\Event\Sqs;

use Bref\Event\LambdaEvent;

/**
 * Represents a Lambda event when Lambda is invoked by SQS.
 */
final class SqsEvent implements LambdaEvent
{
    private array $event;
    private array $records;

    public function __construct(array $records, mixed $event)
    {
        $this->event = $event;
        $this->records = $records;
    }

    /**
     * @return SqsRecord[]
     */
    public function getRecords(): array
    {
        return $this->records;
    }

    public function toArray(): array
    {
        return $this->event;
    }
}
