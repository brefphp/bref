<?php declare(strict_types=1);

namespace Bref\Test\Event\Http;

use Bref\Event\Http\HttpResponseWriter;
use Bref\Runtime\InMemoryResponseStream;
use Bref\Test\Fixture\CallbackResponseStream;
use LogicException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class HttpResponseWriterTest extends TestCase
{
    private const EVENT_V2 = ['version' => '2.0', 'requestContext' => ['http' => ['method' => 'GET']]];

    public function test streamed responses are sent as they are written()
    {
        $lambdaResponse = new InMemoryResponseStream;
        $writer = new HttpResponseWriter($lambdaResponse, self::EVENT_V2, streamed: true);

        $writer->start(404, ['content-type' => 'text/plain', 'set-cookie' => ['a=1', 'b=2']]);
        $this->assertCount(1, $lambdaResponse->chunks);
        $writer->write('Not ');
        $this->assertCount(2, $lambdaResponse->chunks);
        $writer->write('found');

        $this->assertSame([
            '{"cookies":["a=1","b=2"],"statusCode":404,"headers":{"Content-Type":"text\/plain"}}' . str_repeat("\0", 8),
            'Not ',
            'found',
        ], $lambdaResponse->chunks);
        $this->assertFalse($lambdaResponse->ended);
    }

    public function test buffered responses are sent at the end in the format of the invoker()
    {
        $lambdaResponse = new InMemoryResponseStream;
        $writer = new HttpResponseWriter($lambdaResponse, self::EVENT_V2, streamed: false);
        $writer->start(404, ['content-type' => 'text/plain']);
        $writer->write('Not ');
        $writer->write('found');
        // Sent at the end, at once
        $this->assertCount(0, $lambdaResponse->chunks);
        $writer->end();

        $this->assertCount(1, $lambdaResponse->chunks);
        $this->assertTrue($lambdaResponse->ended);
        $response = json_decode($lambdaResponse->contents(), true);
        $this->assertSame(404, $response['statusCode']);
        $this->assertSame(['Content-Type' => 'text/plain'], $response['headers']);
        $this->assertSame('Not found', $response['body']);

        $lambdaResponse = new InMemoryResponseStream;
        $writer = new HttpResponseWriter($lambdaResponse, ['httpMethod' => 'GET', 'multiValueHeaders' => []], streamed: false);
        $writer->start(404, ['content-type' => 'text/plain']);
        $writer->end();
        $this->assertSame(['Content-Type' => ['text/plain']], json_decode($lambdaResponse->contents(), true)['multiValueHeaders']);
    }

    public function test a whole response can be written at once()
    {
        $lambdaResponse = new InMemoryResponseStream;
        $writer = new HttpResponseWriter($lambdaResponse, self::EVENT_V2, streamed: true);

        $writer->respond('Not found', ['content-type' => 'text/plain'], 404);

        $this->assertSame([
            '{"cookies":[],"statusCode":404,"headers":{"Content-Type":"text\/plain"}}' . str_repeat("\0", 8),
            'Not found',
        ], $lambdaResponse->chunks);
        $this->assertTrue($lambdaResponse->ended);
    }

    public function test write tells when the rest is not wanted()
    {
        $lambdaResponse = new CallbackResponseStream(fn (string $chunk) => ! str_contains($chunk, 'stop'));
        $writer = new HttpResponseWriter($lambdaResponse, self::EVENT_V2, streamed: true);
        $writer->start(200, []);

        $this->assertTrue($writer->write('Hello'));
        $this->assertFalse($writer->write('stop'));
    }

    public function test output is captured as it is produced()
    {
        $sent = [];
        $lambdaResponse = new CallbackResponseStream(function (string $chunk) use (&$sent) {
            $sent[] = $chunk;
        });
        $writer = new HttpResponseWriter($lambdaResponse, self::EVENT_V2, streamed: true);
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
        $lambdaResponse = new InMemoryResponseStream;
        $writer = new HttpResponseWriter($lambdaResponse, self::EVENT_V2, streamed: false);
        $writer->start(200, []);

        $writer->capture(function () {
            echo 'Hello';
            // Left open: closed with the capture, else it would swallow the next outputs
            ob_start();
            echo ' world';
        });

        $this->assertSame($level, ob_get_level());
        $writer->end();
        $this->assertSame('Hello', json_decode($lambdaResponse->contents(), true)['body']);
    }

    public function test the status and headers are written once()
    {
        $writer = new HttpResponseWriter(new InMemoryResponseStream, self::EVENT_V2, streamed: false);
        $writer->start(200, []);

        $this->expectException(LogicException::class);
        $writer->start(500, []);
    }

    public function test the body is written after the status and headers()
    {
        $writer = new HttpResponseWriter(new InMemoryResponseStream, self::EVENT_V2, streamed: false);

        $this->expectException(LogicException::class);
        $writer->write('Hello');
    }

    public function test the response ends once()
    {
        $log = [];
        $lambdaResponse = new CallbackResponseStream(function (string $chunk) use (&$log) {
            $log[] = $chunk;
        }, function () use (&$log) {
            $log[] = 'end';
        });
        $writer = new HttpResponseWriter($lambdaResponse, self::EVENT_V2, streamed: true);
        $writer->start(200, []);
        $writer->write('Hello');

        $writer->end();
        $writer->end();

        // Ended once, then nothing more is sent
        $this->assertFalse($writer->write('Too late'));
        $this->assertSame(['Hello', 'end'], array_slice($log, 1));
    }

    public function test a failure to end the response is thrown again by the next end()
    {
        $lambdaResponse = new CallbackResponseStream(fn () => true, fn () => throw new RuntimeException('The response is too big'));

        $writer = new HttpResponseWriter($lambdaResponse, self::EVENT_V2, streamed: true);
        $writer->start(200, []);

        // E.g. a framework that catches the errors of the handler
        try {
            $writer->end();
            $this->fail('end() must throw');
        } catch (RuntimeException) {
        }

        // The runtime ends the response after the handler: it must get the failure
        $this->expectExceptionMessage('The response is too big');
        $writer->end();
    }

    public function test output cannot be captured before the response starts()
    {
        $writer = new HttpResponseWriter(new InMemoryResponseStream, self::EVENT_V2, streamed: true);

        try {
            $writer->capture(function () {
                echo 'Hello';
            });
            $this->fail('capture() must throw');
        } catch (LogicException) {
        }

        // Thrown before the output is produced: it is not printed
        $this->expectOutputString('');
    }

    public function test a response cannot end before it starts()
    {
        $writer = new HttpResponseWriter(new InMemoryResponseStream, self::EVENT_V2, streamed: false);

        $this->expectExceptionMessage('The HTTP handler did not write any response');
        $writer->end();
    }
}
