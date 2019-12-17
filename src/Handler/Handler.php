<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;

interface Handler
{
    public function handle($event, Context $context);
}
