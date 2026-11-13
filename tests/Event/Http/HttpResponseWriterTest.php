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
        $sent = [];
        $writer = HttpResponseWriter::buffered($this->eventV2(), function (string $chunk) use (&$sent) {
            $sent[] = $chunk;
        });
        $writer->start(404, ['content-type' => 'text/plain']);
        $writer->write('Not ');
        $writer->write('found');
        // Sent at the end, at once
        $this->assertCount(0, $sent);
        $writer->end();

        $this->assertCount(1, $sent);
        $response = json_decode($sent[0], true);
        $this->assertSame(404, $response['statusCode']);
        $this->assertSame(['Content-Type' => 'text/plain'], $response['headers']);
        $this->assertSame('Not found', $response['body']);

        $writer = HttpResponseWriter::buffered(new HttpRequestEvent(['httpMethod' => 'GET', 'multiValueHeaders' => []]), function (string $chunk) use (&$sent) {
            $sent[] = $chunk;
        });
        $writer->start(404, ['content-type' => 'text/plain']);
        $writer->end();
        $this->assertSame(['Content-Type' => ['text/plain']], json_decode($sent[1], true)['multiValueHeaders']);
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
        $sent = [];
        $writer = HttpResponseWriter::buffered($this->eventV2(), function (string $chunk) use (&$sent) {
            $sent[] = $chunk;
        });
        $writer->start(200, []);

        $writer->capture(function () {
            echo 'Hello';
            // Left open: closed with the capture, else it would swallow the next outputs
            ob_start();
            echo ' world';
        });

        $this->assertSame($level, ob_get_level());
        $writer->end();
        $this->assertSame('Hello', json_decode($sent[0], true)['body']);
    }

    public function test the status and headers are written once()
    {
        $writer = HttpResponseWriter::buffered($this->eventV2(), fn () => null);
        $writer->start(200, []);

        $this->expectException(LogicException::class);
        $writer->start(500, []);
    }

    public function test the body is written after the status and headers()
    {
        $this->expectException(LogicException::class);
        HttpResponseWriter::buffered($this->eventV2(), fn () => null)->write('Hello');
    }

    public function test the response ends once()
    {
        $log = [];
        $writer = HttpResponseWriter::streamed(function (string $chunk) use (&$log) {
            $log[] = $chunk;
        }, function () use (&$log) {
            $log[] = 'end';
        });
        $writer->start(200, []);
        $writer->write('Hello');

        $writer->end();
        $writer->end();

        // Ended once, then nothing more is sent
        $this->assertFalse($writer->write('Too late'));
        $this->assertSame(['Hello', 'end'], array_slice($log, 1));
    }

    public function test a response cannot end before it starts()
    {
        $this->expectExceptionMessage('The HTTP handler did not write any response');
        HttpResponseWriter::buffered($this->eventV2(), fn () => null)->end();
    }

    private function eventV2(): HttpRequestEvent
    {
        return new HttpRequestEvent(['version' => '2.0', 'requestContext' => ['http' => ['method' => 'GET']]]);
    }
}
