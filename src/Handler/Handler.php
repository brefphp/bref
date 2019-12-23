<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;

/**
 * Handles any kind of Lambda events.
 */
interface Handler
{
    /**
     * @param mixed $event The raw event data.
     * @return mixed|void
     */
    public function handle($event, Context $context);
}
