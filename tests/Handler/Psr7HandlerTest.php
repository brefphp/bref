<?php declare(strict_types=1);

namespace Bref\Test\Handler;

use Bref\Context\Context;
use Bref\Handler\Psr7Handler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Psr7HandlerTest extends TestCase
{
    public function sampleDataProvider()
    {
        return [
            [file_get_contents(dirname(__FILE__, 2) . '/Fixture/Psr7/sample/get_with_cookies.json')],
        ];
    }

    /**
     * @dataProvider sampleDataProvider
     */
    public function test it can create a psr7 request(string $data)
    {
        $handler = new Psr7Handler(function ($r) {
            return $this->getEmptyMockResponse();
        });

        $eventData = json_decode($data, true);
        $context = new Context('1', 0, '', 'asd');
        $response = $handler->__invoke($eventData, $context);
        $this->assertArrayHasKey('isBase64Encoded', $response);
        $this->assertArrayHasKey('statusCode', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('body', $response);
    }

    public function test it can invoke a psr15 responder()
    {
        /** @var RequestHandlerInterface|MockObject $requestHandler */
        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $handler = new Psr7Handler($requestHandler);
        $requestHandler->method('handle')->willReturn($this->getEmptyMockResponse());

        $context = new Context('1', 0, '', 'asd');

        $response = $handler->__invoke([], $context);

        $this->assertArrayHasKey('isBase64Encoded', $response);
        $this->assertArrayHasKey('statusCode', $response);
        $this->assertArrayHasKey('headers', $response);
        $this->assertArrayHasKey('body', $response);
    }

    protected function getEmptyMockResponse(): ResponseInterface
    {
        /** @var ResponseInterface|MockObject $response */
        $response = $this->createMock(ResponseInterface::class);
        $body = $this->createMock(StreamInterface::class);
        $response->method('getBody')->willReturn($body);
        $response->method('getHeaders')->willReturn([]);
        $response->method('getStatusCode')->willReturn(200);
        $body->method('getContents')->willReturn('');

        return $response;
    }
}
