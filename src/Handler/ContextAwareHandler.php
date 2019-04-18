<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Runtime\Context;

interface ContextAwareHandler
{
    /**
     * @param array $event
     * @return mixed Anything that can be serialized to JSON
     */
    public function __invoke(array $event, Context $context);
}
