<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;

interface Handler
{
    /**
     * @param mixed $event The raw event data.
     */
    public function handle($event, Context $context);
}
