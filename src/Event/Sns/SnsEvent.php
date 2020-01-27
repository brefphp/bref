<?php declare(strict_types=1);

namespace Bref\Event\Sns;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;
use InvalidArgumentException;

/**
 * Represents a Lambda event when Lambda is invoked by SNS.
 */
final class SnsEvent implements LambdaEvent
{
    /** @var array */
    private $event;

    /**
     * @param mixed $event
     */
    public function __construct($event)
    {
        if (! is_array($event) || ! isset($event['Records'])) {
            throw new InvalidLambdaEvent('SNS', $event);
        }
        $this->event = $event;
    }

    /**
     * @return SnsRecord[]
     */
    public function getRecords(): array
    {
        return array_map(function ($record): SnsRecord {
            try {
                return new SnsRecord($record);
            } catch (InvalidArgumentException $e) {
                throw new InvalidLambdaEvent('SNS', $this->event);
            }
        }, $this->event['Records']);
    }

    public function toArray(): array
    {
        return $this->event;
    }
}
