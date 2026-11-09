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

        $response = $this->streamedResponse();
        $this->assertSame("Hello world!\n", $response->getBody()->__toString());
        $this->assertFalse($response->hasHeader('lambda-runtime-function-error-type'));
    }

    public function test failure()
    {
        $this->givenAnEvent('fail');

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

    public function test output above 6MB is sent whole()
    {
        $this->givenAnEvent('flood');

        try {
            Main::run();
        } catch (\Throwable) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        // Lambda refuses it with buffered invocations, InvokeWithResponseStream accepts up to 200 MB
        $this->assertSame(7 * 1024 * 1024, strlen($this->streamedResponse()->getBody()->__toString()));
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
