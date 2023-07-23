<?php declare(strict_types=1);

namespace Bref\Test\Listener;

use Bref\Context\Context;
use Bref\Listener\EventDispatcher;
use PHPUnit\Framework\TestCase;
use stdClass;

class EventDispatcherTest extends TestCase
{
    public function test subscribe(): void
    {
        $eventDispatcher = new EventDispatcher;
        $subscriber = new FakeSubscriber;
        $eventDispatcher->subscribe($subscriber);

        $eventDispatcher->beforeStartup();
        $this->assertTrue($subscriber->invokedBeforeStartup);

        $handler = fn() => null;
        $event = new stdClass;
        $context = Context::fake();
        $eventDispatcher->beforeInvoke($handler, $event, $context);
        $this->assertEquals([$handler, $event, $context], $subscriber->invokedBeforeInvoke);

        $result = new stdClass;
        $eventDispatcher->afterInvoke($handler, $event, $context, $result);
        $this->assertEquals([$handler, $event, $context, $result, null], $subscriber->invokedAfterInvoke);
    }
}