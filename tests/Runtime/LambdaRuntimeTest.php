<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Handler\ContextAwareHandler;
use Bref\Runtime\LambdaRuntime;
use Bref\Test\Server;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Tests the communication between `LambdaRuntime` and the Lambda Runtime HTTP API.
 *
 * The API is mocked using a fake HTTP server.
 */
class LambdaRuntimeTest extends TestCase
{
    /** @var LambdaRuntime */
    private $runtime;

    protected function setUp()
    {
        Server::start();
        $this->runtime = new LambdaRuntime('localhost:8126');
    }

    protected function tearDown()
    {
        Server::stop();
    }

    public function test basic behavior()
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => 1,
                ],
                '{ "Hello": "world!"}'
            ),
            new Response(200), // lambda response accepted
        ]);

        $this->runtime->processNextEvent(function () {
            return ['hello' => 'world'];
        });

        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventResponse->getUri()->__toString());
        $this->assertJsonStringEqualsJsonString('{"hello": "world"}', $eventResponse->getBody()->__toString());
    }

    public function test context aware handler()
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => 1,
                    'x-context-test-name' => 'test-value',
                ],
                '{ "Hello": "world!"}'
            ),
            new Response(200), // lambda response accepted
        ]);

        $handler = new class implements ContextAwareHandler {
            public function __invoke(array $event, array $context): array
            {
                return ['hello' => 'world', 'received-context-test-name' => $context['x-context-test-name']];
            }
        };

        $this->runtime->processNextEvent($handler);

        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventResponse->getUri()->__toString());
        $this->assertJsonStringEqualsJsonString('{"hello": "world", "received-context-test-name": "test-value"}', $eventResponse->getBody()->__toString());
    }

    public function test an error is thrown if the runtime API returns a wrong response()
    {
        $this->expectExceptionMessage('Failed to fetch next Lambda invocation: The requested URL returned error: 404 Not Found');
        Server::enqueue([
            new Response( // lambda event
                404, // 404 instead of 200
                [
                    'lambda-runtime-aws-request-id' => 1,
                ],
                '{ "Hello": "world!"}'
            ),
        ]);

        $this->runtime->processNextEvent(function ($event) {
        });
    }

    public function test an error is thrown if the invocation id is missing()
    {
        $this->expectExceptionMessage('Failed to determine the Lambda invocation ID');
        Server::enqueue([
            new Response( // lambda event
                200,
                [], // Missing `lambda-runtime-aws-request-id`
                '{ "Hello": "world!"}'
            ),
        ]);

        $this->runtime->processNextEvent(function () {
        });
    }

    public function test an error is thrown if the invocation body is empty()
    {
        $this->expectExceptionMessage('Empty Lambda runtime API response');
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => 1,
                ]
            ),
        ]);

        $this->runtime->processNextEvent(function () {
        });
    }

    public function test a wrong response from the runtime API turns the invocation into an error()
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => 1,
                ],
                '{ "Hello": "world!"}'
            ),
            new Response(400), // The Lambda API returns a 400 instead of a 200
            new Response(200),
        ]);

        $this->runtime->processNextEvent(function ($event) {
            return $event;
        });
        $requests = Server::received();
        $this->assertCount(3, $requests);

        [$eventRequest, $eventFailureResponse, $eventFailureLog] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventFailureResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventFailureResponse->getUri()->__toString());
        $this->assertSame('POST', $eventFailureLog->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/error', $eventFailureLog->getUri()->__toString());

        $error = json_decode((string) $eventFailureLog->getBody());
        $this->expectOutputRegex('/^Fatal error: Uncaught Exception: Error while calling the Lambda runtime API: The requested URL returned error: 400 Bad Request/');
        $this->assertSame('Error while calling the Lambda runtime API: The requested URL returned error: 400 Bad Request', $error->errorMessage);
    }

    public function test function results that cannot be encoded are reported as invocation errors()
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => 1,
                ],
                '{ "Hello": "world!"}'
            ),
            new Response(200), // lambda response accepted
        ]);

        $this->runtime->processNextEvent(function () {
            return "\xB1\x31";
        });

        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventFailureLog] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventFailureLog->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/error', $eventFailureLog->getUri()->__toString());

        $error = json_decode((string) $eventFailureLog->getBody());
        $this->expectOutputRegex('/^Fatal error: Uncaught Exception: Failed encoding Lambda JSON response: Malformed UTF-8 characters, possibly incorrectly encoded/');
        $this->assertSame('Failed encoding Lambda JSON response: Malformed UTF-8 characters, possibly incorrectly encoded', $error->errorMessage);
    }
}
