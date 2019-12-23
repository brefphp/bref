<?php declare(strict_types=1);

namespace Bref\Event;

/**
 * Represents a Lambda event.
 *
 * This interface contains the methods that are common to all events.
 */
interface LambdaEvent
{
    /**
     * Returns the event original data as an array.
     *
     * Use this method if you want to access data that is not returned by a method in this class.
     */
    public function toArray(): array;
}
