<?php declare(strict_types=1);

namespace Bref\Event;

use Exception;

final class InvalidLambdaEvent extends Exception
{
    /**
     * @param mixed $event
     */
    public function __construct(string $expectedEventType, $event)
    {
        if (! $event) {
            $eventData = 'null';
        } else {
            $eventData = print_r($event, true);
        }

        parent::__construct("This handler expected to be invoked with a $expectedEventType event. Instead, the handler was invoked with invalid event data: $eventData");
    }
}
