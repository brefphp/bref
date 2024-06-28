<?php declare(strict_types=1);

namespace Bref\Test\Listener;

use Bref\Listener\BrefEventSubscriber;

class FakeSubscriber extends BrefEventSubscriber
{
    public bool $invokedBeforeStartup = false;
    public mixed $invokedBeforeInvoke = null;
    public mixed $invokedAfterInvoke = null;

    public function beforeStartup(): void
    {
        $this->invokedBeforeStartup = true;
    }

    /**
     * @param mixed ...$params
     */
    public function beforeInvoke(...$params): void
    {
        $this->invokedBeforeInvoke = $params;
    }

    /**
     * @param mixed ...$params
     */
    public function afterInvoke(...$params): void
    {
        $this->invokedAfterInvoke = $params;
    }
}
