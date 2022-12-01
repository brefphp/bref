<?php declare(strict_types=1);

namespace Bref\Runtime;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Event\Http\Psr15Handler;
use Exception;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 */
final class Invoker
{
    public function invoke(mixed $handler, mixed $event, Context $context): mixed
    {
        // PSR-15 adapter
        if ($handler instanceof RequestHandlerInterface) {
            $handler = new Psr15Handler($handler);
        }

        if ($handler instanceof Handler) {
            return $handler->handle($event, $context);
        }

        if (is_callable($handler)) {
            // The handler is a callable
            return $handler($event, $context);
        }

        throw new Exception('The lambda handler must be a callable or implement handler interfaces');
    }
}
