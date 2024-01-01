<?php declare(strict_types=1);

namespace Bref\Listener;

use Bref\Context\Context;
// @phpcs:disable
use Bref\Event\Handler;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Listen to Bref internal events.
 *
 * Warning: Bref events are low-level extension points to be used by framework
 * integrations. For user code, it is not recommended to use them. Use your
 * framework's extension points instead.
 *
 * @internal This API is experimental and may change at any time.
 */
abstract class BrefEventSubscriber
{
    /**
     * Register a hook to be executed before the runtime starts.
     */
    public function beforeStartup(): void
    {
    }

    /**
     * Register a hook to be executed after the runtime has started.
     */
    public function afterStartup(): void
    {
    }

    /**
     * Register a hook to be executed before any Lambda invocation.
     */
    public function beforeInvoke(
        callable | Handler | RequestHandlerInterface $handler,
        mixed $event,
        Context $context,
    ): void {
    }

    /**
     * Register a hook to be executed after any Lambda invocation.
     *
     * In case of an error, the `$error` parameter will be set and
     * `$result` will be `null`.
     */
    public function afterInvoke(
        callable | Handler | RequestHandlerInterface $handler,
        mixed $event,
        Context $context,
        mixed $result,
        \Throwable | null $error = null,
    ): void {
    }
}
