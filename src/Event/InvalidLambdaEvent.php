<?php declare(strict_types=1);

namespace Bref\Event;

use Exception;

final class InvalidLambdaEvent extends Exception
{
    public function __construct(string $expectedEventType, mixed $event)
    {
        if (! $event) {
            $eventData = 'null';
        } else {
            $eventData = print_r($event, true);
        }

        parent::__construct("This handler expected to be invoked with a $expectedEventType event (check that you are using the correct Bref runtime: https://bref.sh/docs/runtimes/#bref-runtimes).\nInstead, the handler was invoked with invalid event data: $eventData");
    }
}
