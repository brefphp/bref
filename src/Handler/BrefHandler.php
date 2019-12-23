<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;

/**
 * Handles any kind of Lambda events.
 */
interface BrefHandler
{
    /**
     * @param mixed $event The raw event data.
     * @return mixed|void
     */
    public function __invoke($event, Context $context);
}
