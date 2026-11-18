<?php declare(strict_types=1);

namespace Bref\Test\ConsoleRuntime;

use Bref\ConsoleRuntime\CommandFailed;
use Bref\ConsoleRuntime\Main;
use Bref\Test\RuntimeTestCase;
use Bref\Test\Server;
use Psr\Http\Message\RequestInterface;

class MainTest extends RuntimeTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        putenv('LAMBDA_TASK_ROOT=' . __DIR__);
        putenv('_HANDLER=console.php');
    }

    public function test happy path()
    {
        $this->givenAnEvent('');

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $this->assertInvocationResult([
            'exitCode' => 0,
            'output' => "Hello world!\n",
        ]);
    }

    public function test failure()
    {
        $this->givenAnEvent('fail');

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $this->assertInvocationErrorResult(CommandFailed::class, "Hello world!\nFailure\n");
    }

    public function test trims output to stay under the 6MB limit of Lambda()
    {
        $this->givenAnEvent('flood');

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $requests = Server::received();
        $this->assertCount(2, $requests);

        [, $eventResponse] = $requests;
        $this->assertLessThan(6 * 1024 * 1024, strlen($eventResponse->getBody()->__toString()));
        // Check the content of the result can be decoded
        $result = json_decode($eventResponse->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(0, $result['exitCode']);
    }

    public function test the object event without the stream flag returns the output at the end()
    {
        $this->givenAnEvent(['cli' => '']);

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $this->assertInvocationResult([
            'exitCode' => 0,
            'output' => "Hello world!\n",
        ]);
    }

    public function test streamed output()
    {
        $this->givenAnEvent(['cli' => '', 'stream' => true]);

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $response = $this->streamedResponse();
        $this->assertSame('text/plain', $response->getHeaderLine('content-type'));
        $this->assertSame("Hello world!\n", $response->getBody()->__toString());
        $this->assertFalse($response->hasHeader('lambda-runtime-function-error-type'));
    }

    public function test streamed failure()
    {
        $this->givenAnEvent(['cli' => 'fail', 'stream' => true]);

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        // The output is sent, the failure is in the trailers: Lambda never retries a failing command
        $response = $this->streamedResponse();
        $this->assertSame("Hello world!\nFailure\n", $response->getBody()->__toString());
        $this->assertSame(CommandFailed::class, $response->getHeaderLine('lambda-runtime-function-error-type'));
    }

    public function test streamed failure without output()
    {
        $this->givenAnEvent(['cli' => 'silent-fail', 'stream' => true]);

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        // Also in the trailers, not with the error endpoint (retried for asynchronous invocations)
        $response = $this->streamedResponse();
        $this->assertSame('', $response->getBody()->__toString());
        $this->assertSame(CommandFailed::class, $response->getHeaderLine('lambda-runtime-function-error-type'));
        $error = json_decode(base64_decode($response->getHeaderLine('lambda-runtime-function-error-body')), true);
        $this->assertSame('The command exited with code 1', $error['errorMessage']);
    }

    public function test streamed output above 6MB is sent whole()
    {
        $this->givenAnEvent(['cli' => 'flood', 'stream' => true]);

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        // Lambda refuses it with buffered invocations, InvokeWithResponseStream accepts up to 200 MB
        $this->assertSame(7 * 1024 * 1024, strlen($this->streamedResponse()->getBody()->__toString()));
    }

    public function test invalid UTF8 in the output is replaced()
    {
        $this->givenAnEvent('invalid-utf8');

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $this->assertInvocationResult([
            'exitCode' => 0,
            'output' => "Invalid UTF-8: ?1\n",
        ]);
    }

    public function test trimming the output never fails on a character cut in half()
    {
        $this->givenAnEvent('flood-multibyte');

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $requests = Server::received();
        $this->assertCount(2, $requests);

        [, $eventResponse] = $requests;
        $this->assertStringEndsWith('/response', $eventResponse->getUri()->getPath());
        $result = json_decode($eventResponse->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals(0, $result['exitCode']);
        $this->assertStringEndsWith('€€€', $result['output']);
    }

    private function streamedResponse(): RequestInterface
    {
        $requests = Server::received();
        $this->assertCount(2, $requests);
        [, $response] = $requests;
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $response->getUri()->__toString());
        $this->assertSame('streaming', $response->getHeaderLine('lambda-runtime-function-response-mode'));

        return $response;
    }
}
