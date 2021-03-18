<?php declare(strict_types=1);

namespace Bref\Test\Event\ApiGateway;

use Bref\Event\ApiGateway\WebsocketEvent;
use PHPUnit\Framework\TestCase;

class WebsocketEventTest extends TestCase
{
    public function test_connect()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/Samples/websocket-connect.json'), true);
        $event = new WebsocketEvent($event);

        $this->assertSame('CONNECT', $event->getEventType());
        $this->assertSame('xyz-apiId', $event->getApiId());
        $this->assertSame('xyz-connectionId', $event->getConnectionId());
        $this->assertSame('xyz-apiId.execute-api.eu-west-1.amazonaws.com', $event->getDomainName());
        $this->assertSame('eu-west-1', $event->getRegion());
        $this->assertSame('$connect', $event->getRouteKey());
        $this->assertSame('dev', $event->getStage());
        $this->assertNull($event->getBody());
    }

    public function test_disconnect()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/Samples/websocket-disconnect.json'), true);
        $event = new WebsocketEvent($event);

        $this->assertSame('DISCONNECT', $event->getEventType());
        $this->assertSame('xyz-apiId', $event->getApiId());
        $this->assertSame('xyz-connectionId', $event->getConnectionId());
        $this->assertSame('xyz-apiId.execute-api.eu-west-1.amazonaws.com', $event->getDomainName());
        $this->assertSame('eu-west-1', $event->getRegion());
        $this->assertSame('$disconnect', $event->getRouteKey());
        $this->assertSame('dev', $event->getStage());
        $this->assertNull($event->getBody());
    }

    public function test_message()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/Samples/websocket-message.json'), true);
        $event = new WebsocketEvent($event);

        $this->assertSame('MESSAGE', $event->getEventType());
        $this->assertSame('xyz-apiId', $event->getApiId());
        $this->assertSame('xyz-connectionId', $event->getConnectionId());
        $this->assertSame('xyz-apiId.execute-api.eu-west-1.amazonaws.com', $event->getDomainName());
        $this->assertSame('eu-west-1', $event->getRegion());
        $this->assertSame('$default', $event->getRouteKey());
        $this->assertSame('dev', $event->getStage());
        $this->assertSame('Hello Server!', $event->getBody());
    }
}
