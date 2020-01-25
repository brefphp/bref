<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Event\S3\S3Event;
use Bref\Event\S3\S3Handler;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsHandler;
use Bref\Runtime\LambdaRuntime;
use Bref\Test\Server;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
        $this->givenAnEvent(['Hello' => 'world!']);

        $this->runtime->processNextEvent(function () {
            return ['hello' => 'world'];
        });

        $this->assertInvocationResult(['hello' => 'world']);
    }

    public function test handler receives context()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $this->runtime->processNextEvent(function (array $event, Context $context) {
            return ['hello' => 'world', 'received-function-arn' => $context->getInvokedFunctionArn()];
        });

        $this->assertInvocationResult([
            'hello' => 'world',
            'received-function-arn' => 'test-function-name',
        ]);
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
        $this->givenAnEvent(['hello' => 'world!']);

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
        $this->expectOutputRegex('/^Fatal error: Uncaught Exception: The Lambda response cannot be encoded to JSON/');
        $this->assertSame("The Lambda response cannot be encoded to JSON.\nThis error usually happens when you try to return binary content. If you are writing a HTTP application and you want to return a binary HTTP response (like an image, a PDF, etc.), please read this guide: https://bref.sh/docs/runtimes/http.html#binary-responses\nHere is the original JSON error: 'Malformed UTF-8 characters, possibly incorrectly encoded'", $error->errorMessage);
    }

    public function test generic event handler()
    {
        $handler = new class() implements Handler {
            /** @param mixed $event */
            public function handle($event, Context $context)
            {
                return $event;
            }
        };

        $this->givenAnEvent(['foo' => 'bar']);

        $this->runtime->processNextEvent($handler);

        $this->assertInvocationResult(['foo' => 'bar']);
    }

    public function test SQS event handler()
    {
        $handler = new class() implements SqsHandler {
            /** @var SqsEvent */
            public $event;
            public function handle(SqsEvent $event, Context $context): void
            {
                $this->event = $event;
            }
        };

        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/Sqs/sqs.json'), true);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent($handler);

        $this->assertEquals(new SqsEvent($eventData), $handler->event);
    }

    public function test S3 event handler()
    {
        $handler = new class() implements S3Handler {
            /** @var S3Event */
            public $event;
            public function handle(S3Event $event, Context $context): void
            {
                $this->event = $event;
            }
        };

        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/S3/s3.json'), true);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent($handler);

        $this->assertEquals(new S3Event($eventData), $handler->event);
    }

    public function test PSR7 event handler()
    {
        $handler = new class() implements RequestHandlerInterface {
            /** @var ServerRequestInterface */
            public $request;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new Response(200, [
                    'Content-Type' => 'text/html',
                ], 'Hello world!');
            }
        };

        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/Http/Fixture/apigateway-simple.json'), true);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent($handler);

        $this->assertEquals('GET', $handler->request->getMethod());
        $this->assertEquals('/path', (string) $handler->request->getUri());
        $this->assertInvocationResult([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'text/html',
            ],
            'body' => 'Hello world!',
        ]);
    }

    public function test invalid handlers are rejected properly()
    {
        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/Http/Fixture/apigateway-simple.json'), true);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent(null);

        $requests = Server::received();
        $this->assertCount(2, $requests);
        $error = json_decode((string) $requests[1]->getBody(), true);
        $this->expectOutputRegex('/^Fatal error: Uncaught Exception: The lambda handler must be a callable or implement handler interfaces/');
        $this->assertSame('The lambda handler must be a callable or implement handler interfaces', $error['errorMessage']);
    }

    /**
     * @param mixed $event
     */
    private function givenAnEvent($event): void
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => 1,
                    'lambda-runtime-invoked-function-arn' => 'test-function-name',
                ],
                json_encode($event)
            ),
            new Response(200), // lambda response accepted
        ]);
    }

    /**
     * @param mixed $result
     */
    private function assertInvocationResult($result)
    {
        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventResponse->getUri()->__toString());
        $this->assertEquals($result, json_decode($eventResponse->getBody()->__toString(), true));
    }
}
