<?php declare(strict_types=1);

namespace Bref\Listener;

use Bref\Context\Context;
// @phpcs:disable
use Bref\Event\Handler;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal This API is experimental and may change at any time.
 */
final class EventDispatcher extends BrefEventSubscriber
{
    /**
     * @param BrefEventSubscriber[] $subscribers
     */
    public function __construct(
        private array $subscribers = [],
    ) {
    }

    /**
     * Register an event subscriber class.
     */
    public function subscribe(BrefEventSubscriber $subscriber): void
    {
        $this->subscribers[] = $subscriber;
    }

    /**
     * Trigger the `beforeStartup` event.
     *
     * @internal This method is called by Bref and should not be called by user code.
     */
    public function beforeStartup(): void
    {
        foreach ($this->subscribers as $listener) {
            $listener->beforeStartup();
        }
    }

    /**
     * Trigger the `afterStartup` event.
     *
     * @internal This method is called by Bref and should not be called by user code.
     */
    public function afterStartup(): void
    {
        foreach ($this->subscribers as $listener) {
            $listener->afterStartup();
        }
    }

    /**
     * Trigger the `beforeInvoke` event.
     *
     * @internal This method is called by Bref and should not be called by user code.
     */
    public function beforeInvoke(
        callable | Handler | RequestHandlerInterface $handler,
        mixed $event,
        Context $context,
    ): void {
        foreach ($this->subscribers as $listener) {
            $listener->beforeInvoke($handler, $event, $context);
        }
    }

    /**
     * Trigger the `afterInvoke` event.
     *
     * @internal This method is called by Bref and should not be called by user code.
     */
    public function afterInvoke(
        callable | Handler | RequestHandlerInterface $handler,
        mixed $event,
        Context $context,
        mixed $result,
        \Throwable | null $error = null,
    ): void {
        foreach ($this->subscribers as $listener) {
            $listener->afterInvoke($handler, $event, $context, $result, $error);
        }
    }
}
