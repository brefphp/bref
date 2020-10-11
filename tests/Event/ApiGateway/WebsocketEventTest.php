<?php declare(strict_types=1);

namespace Bref\Test\Event\DynamoDb;

use Bref\Event\ApiGateway\WebsocketEvent;
use Bref\Event\ApiGateway\WebsocketResponse;
use PHPUnit\Framework\TestCase;

class WebsocketEventTest extends TestCase
{
    public function test_connect()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/websocket-connect.json'), true);
        $event = new WebsocketEvent($event);

        $this->assertSame(WebsocketEvent::EVENT_TYPE_CONNECT, $event->getEventType());
        $this->assertSame('xyz-apiId', $event->getApiId());
        $this->assertSame('xyz-connectionId', $event->getConnectionId());
        $this->assertSame('xyz-apiId.execute-api.eu-west-1.amazonaws.com', $event->getDomainName());
        $this->assertSame('$connect', $event->getRouteKey());
        $this->assertSame('dev', $event->getStage());
        $this->assertNull($event->getBody());
    }

    public function test_disconnect()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/websocket-disconnect.json'), true);
        $event = new WebsocketEvent($event);

        $this->assertSame(WebsocketEvent::EVENT_TYPE_DISCONNECT, $event->getEventType());
        $this->assertSame('xyz-apiId', $event->getApiId());
        $this->assertSame('xyz-connectionId', $event->getConnectionId());
        $this->assertSame('xyz-apiId.execute-api.eu-west-1.amazonaws.com', $event->getDomainName());
        $this->assertSame('$disconnect', $event->getRouteKey());
        $this->assertSame('dev', $event->getStage());
        $this->assertNull($event->getBody());
    }

    public function test_message()
    {
        $event = json_decode(file_get_contents(__DIR__ . '/websocket-message.json'), true);
        $event = new WebsocketEvent($event);

        $this->assertSame(WebsocketEvent::EVENT_TYPE_MESSAGE, $event->getEventType());
        $this->assertSame('xyz-apiId', $event->getApiId());
        $this->assertSame('xyz-connectionId', $event->getConnectionId());
        $this->assertSame('xyz-apiId.execute-api.eu-west-1.amazonaws.com', $event->getDomainName());
        $this->assertSame('$default', $event->getRouteKey());
        $this->assertSame('dev', $event->getStage());
        $this->assertSame('Hello Server!', $event->getBody());
    }

    public function test_response_default()
    {
        $response = new WebsocketResponse;
        $apiGatewayResponse = $response->toApiGatewayFormat();

        $this->assertIsArray($apiGatewayResponse);
        $this->assertArrayHasKey('statusCode', $apiGatewayResponse);
        $this->assertSame(200, $apiGatewayResponse['statusCode']);
    }

    public function test_response_internal_server_error()
    {
        $response = new WebsocketResponse(500);
        $apiGatewayResponse = $response->toApiGatewayFormat();

        $this->assertIsArray($apiGatewayResponse);
        $this->assertArrayHasKey('statusCode', $apiGatewayResponse);
        $this->assertSame(500, $apiGatewayResponse['statusCode']);
    }
}
