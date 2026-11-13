<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponseWriter;
use LogicException;
use PHPUnit\Framework\TestCase;

class HttpResponseWriterTest extends TestCase
{
    public function test streamed responses are sent as they are written()
    {
        $sent = [];
        $writer = HttpResponseWriter::streamed(function (string $chunk) use (&$sent) {
            $sent[] = $chunk;
        });

        $writer->start(404, ['content-type' => 'text/plain', 'set-cookie' => ['a=1', 'b=2']]);
        $writer->write('Not ');
        $writer->write('');
        $writer->write('found');

        $this->assertSame([
            '{"cookies":["a=1","b=2"],"statusCode":404,"headers":{"Content-Type":"text\/plain"}}' . str_repeat("\0", 8),
            'Not ',
            'found',
        ], $sent);
    }

    public function test buffered responses are returned in the format of the invoker()
    {
        $writer = HttpResponseWriter::buffered();
        $writer->start(404, ['content-type' => 'text/plain']);
        $writer->write('Not ');
        $writer->write('found');

        $response = $writer->toApiGatewayFormat(new HttpRequestEvent(['version' => '2.0', 'requestContext' => ['http' => ['method' => 'GET']]]));
        $this->assertSame(404, $response['statusCode']);
        $this->assertSame(['Content-Type' => 'text/plain'], $response['headers']);
        $this->assertSame('Not found', $response['body']);

        $response = $writer->toApiGatewayFormat(new HttpRequestEvent(['httpMethod' => 'GET', 'multiValueHeaders' => []]));
        $this->assertSame(['Content-Type' => ['text/plain']], $response['multiValueHeaders']);
    }

    public function test write tells when the rest is not wanted()
    {
        $writer = HttpResponseWriter::streamed(fn (string $chunk) => ! str_contains($chunk, 'stop'));
        $writer->start(200, []);

        $this->assertTrue($writer->write('Hello'));
        $this->assertFalse($writer->write('stop'));
    }

    public function test output is captured as it is produced()
    {
        $sent = [];
        $writer = HttpResponseWriter::streamed(function (string $chunk) use (&$sent) {
            $sent[] = $chunk;
        });
        $writer->start(200, []);

        $writer->capture(function () use (&$sent) {
            echo 'Hello';
            $sent[] = 'between';
            // Nested buffers, like templates do
            ob_start();
            echo ' world';
            ob_end_flush();
        });

        $this->assertSame(['Hello', 'between', ' world'], array_slice($sent, 1));
    }

    public function test the output buffers are restored after capturing()
    {
        $level = ob_get_level();
        $writer = HttpResponseWriter::buffered();
        $writer->start(200, []);

        $writer->capture(function () {
            echo 'Hello';
            // Left open: closed with the capture, else it would swallow the next outputs
            ob_start();
            echo ' world';
        });

        $this->assertSame($level, ob_get_level());
        $response = $writer->toApiGatewayFormat(new HttpRequestEvent(['version' => '2.0', 'requestContext' => ['http' => ['method' => 'GET']]]));
        $this->assertSame('Hello', $response['body']);
    }

    public function test the status and headers are written once()
    {
        $writer = HttpResponseWriter::buffered();
        $writer->start(200, []);

        $this->expectException(LogicException::class);
        $writer->start(500, []);
    }

    public function test the body is written after the status and headers()
    {
        $this->expectException(LogicException::class);
        HttpResponseWriter::buffered()->write('Hello');
    }
}
