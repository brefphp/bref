<?php declare(strict_types=1);

namespace Bref\Test\Event\DynamoDb;

use Bref\Event\ApiGateway\WebsocketResponse;
use PHPUnit\Framework\TestCase;

class WebsocketResponseTest extends TestCase
{
    public function test_default()
    {
        $response = new WebsocketResponse;
        $apiGatewayResponse = $response->toApiGatewayFormat();

        $this->assertIsArray($apiGatewayResponse);
        $this->assertArrayHasKey('statusCode', $apiGatewayResponse);
        $this->assertSame(200, $apiGatewayResponse['statusCode']);
    }

    public function test_internal_server_error()
    {
        $response = new WebsocketResponse(500);
        $apiGatewayResponse = $response->toApiGatewayFormat();

        $this->assertIsArray($apiGatewayResponse);
        $this->assertArrayHasKey('statusCode', $apiGatewayResponse);
        $this->assertSame(500, $apiGatewayResponse['statusCode']);
    }

    public function test_protocol()
    {
        $response = new WebsocketResponse(200, [
            'protocol1',
            'protocol2',
        ]);

        $apiGatewayResponse = $response->toApiGatewayFormat();

        $this->assertIsArray($apiGatewayResponse);
        $this->assertArrayHasKey('headers', $apiGatewayResponse);
        $this->assertIsArray($apiGatewayResponse['headers']);
        $this->assertArrayHasKey('Sec-WebSocket-Protocol', $apiGatewayResponse['headers']);
        $this->assertSame('protocol1, protocol2', $apiGatewayResponse['headers']['Sec-WebSocket-Protocol']);
    }
}
