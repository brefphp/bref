<?php declare(strict_types=1);

namespace Bref\Listener;

use Bref\Context\Context;
use Bref\Event\Handler;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class EventDispatcher extends BrefEventSubscriber
{
    /**
     * @param BrefEventSubscriber[] $subscribers
     */
    public function __construct(
        private array $subscribers = [],
    )
    {
    }

    public function subscribe(BrefEventSubscriber $subscriber): void
    {
        $this->subscribers[] = $subscriber;
    }

    public function beforeStartup(): void
    {
        foreach ($this->subscribers as $listener) {
            $listener->beforeStartup();
        }
    }

    public function beforeInvoke(
        Handler|RequestHandlerInterface|callable $handler,
        mixed $event,
        Context $context,
    ): void
    {
        foreach ($this->subscribers as $listener) {
            $listener->beforeInvoke($handler, $event, $context);
        }
    }

    public function afterInvoke(
        Handler|RequestHandlerInterface|callable $handler,
        mixed $event,
        Context $context,
        mixed $result,
        Throwable|null $error = null,
    ): void
    {
        foreach ($this->subscribers as $listener) {
            $listener->afterInvoke($handler, $event, $context, $result, $error);
        }
    }
}