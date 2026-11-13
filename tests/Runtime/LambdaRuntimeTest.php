<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Bref;
use Bref\Context\Context;
use Bref\Event\EventBridge\EventBridgeEvent;
use Bref\Event\EventBridge\EventBridgeHandler;
use Bref\Event\Handler;
use Bref\Event\Http\HttpHandler;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Event\Http\HttpResponse;
use Bref\Event\S3\S3Event;
use Bref\Event\S3\S3Handler;
use Bref\Event\Sns\SnsEvent;
use Bref\Event\Sns\SnsHandler;
use Bref\Event\Sqs\SqsEvent;
use Bref\Event\Sqs\SqsHandler;
use Bref\Listener\BrefEventSubscriber;
use Bref\Runtime\LambdaRuntime;
use Bref\Runtime\ResponseTimedOut;
use Bref\Runtime\ResponseTooBig;
use Bref\Runtime\StreamedResponse;
use Bref\Test\RuntimeTestCase;
use Bref\Test\Server;
use Exception;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Tests the communication between `LambdaRuntime` and the Lambda Runtime HTTP API.
 *
 * The API is mocked using a fake HTTP server.
 */
class LambdaRuntimeTest extends RuntimeTestCase
{
    private LambdaRuntime $runtime;

    protected function setUp(): void
    {
        parent::setUp();

        Bref::reset();

        $this->runtime = new LambdaRuntime('localhost:8126', 'phpunit');
    }

    protected function tearDown(): void
    {
        putenv('BREF_RESPONSE_STREAMING');
        parent::tearDown();
    }

    public function test basic behavior()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $output = $this->runtime->processNextEvent(function () {
            return ['hello' => 'world'];
        });

        $this->assertTrue($output);
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

    public function test exceptions in the handler result in an invocation error()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $output = $this->runtime->processNextEvent(function () {
            throw new RuntimeException('This is an exception');
        });

        $this->assertFalse($output);
        $this->assertInvocationErrorResult('RuntimeException', 'This is an exception');
        $this->assertErrorInLogs('RuntimeException', 'This is an exception');
    }

    public function test nested exceptions in the handler result in an invocation error()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $this->runtime->processNextEvent(function () {
            throw new RuntimeException('This is an exception', 0, new RuntimeException('The previous exception.', 0, new Exception('The original exception.')));
        });

        $this->assertInvocationErrorResult('RuntimeException', 'This is an exception');
        $this->assertErrorInLogs('RuntimeException', 'This is an exception');
        $this->assertPreviousErrorsInLogs([
            ['errorClass' => 'RuntimeException', 'errorMessage' => 'The previous exception.'],
            ['errorClass' => 'Exception', 'errorMessage' => 'The original exception.'],
        ]);
    }

    public function test a streamed response is sent chunk by chunk()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', (function () {
            yield 'Hello ';
            yield '';
            yield 'world!';
        })()));

        $response = $this->streamedResponse();
        $this->assertSame('text/plain', $response->getHeaderLine('Content-Type'));
        $this->assertSame('Hello world!', $response->getBody()->__toString());
        $this->assertFalse($response->hasHeader('lambda-runtime-function-error-type'));
    }

    public function test a streamed response can push its chunks()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', function (callable $write) {
            $write('Hello ');
            $write('world!');
        }));

        $this->assertSame('Hello world!', $this->streamedResponse()->getBody()->__toString());
    }

    public function test a streamed response that fails before its first byte is an invocation error()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $output = $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', function () {
            throw new RuntimeException('Failure before the first byte');
        }));

        $this->assertFalse($output);
        $this->assertInvocationErrorResult('RuntimeException', 'Failure before the first byte');
    }

    public function test a streamed response that fails after its first byte reports the error in the trailers()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $output = $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', (function () {
            yield 'Hello';
            throw new RuntimeException('Failure in the middle');
        })()));

        $this->assertFalse($output);
        // The status is already sent: the error is in the trailers, which Lambda counts in the error metrics
        $response = $this->streamedResponse();
        $this->assertSame('Hello', $response->getBody()->__toString());
        $this->assertSame('RuntimeException', $response->getHeaderLine('lambda-runtime-function-error-type'));
        $error = json_decode(base64_decode($response->getHeaderLine('lambda-runtime-function-error-body')), true);
        $this->assertSame('Failure in the middle', $error['errorMessage']);
        $this->assertErrorInLogs('RuntimeException', 'Failure in the middle');
    }

    public function test init errors are reported without their stack trace()
    {
        Server::enqueue([new Response(202)]);

        // It ends the process: run it in another one
        $process = new Process([
            PHP_BINARY,
            '-r',
            <<<'PHP'
            require 'vendor/autoload.php';
            (new Bref\Runtime\LambdaRuntime('localhost:8126', 'phpunit'))->failInitialization(new RuntimeException('Init failure'));
            PHP,
        ], dirname(__DIR__, 2));
        $process->run();

        $this->assertSame(1, $process->getExitCode());
        [$request] = Server::received();
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/init/error', $request->getUri()->__toString());
        // Function URLs in RESPONSE_STREAM mode show it to the client: no stack trace
        $this->assertSame([
            'errorMessage' => 'Init failure',
            'errorType' => 'RuntimeException',
            'stackTrace' => [],
        ], json_decode($request->getBody()->__toString(), true));
        // The stack trace is in the logs
        $this->assertStringContainsString('Fatal error: RuntimeException: Init failure', $process->getOutput());
        $this->assertStringContainsString('stackTrace', $process->getOutput());
    }

    public function test a streamed response still being sent close to the timeout is ended with an error()
    {
        // Less than 1 second left
        $this->givenAnEvent(['Hello' => 'world!'], (int) (microtime(true) * 1000) + 500);
        $pulled = 0;

        $chunks = (function () use (&$pulled) {
            foreach (['Hello', ' world', '!'] as $chunk) {
                $pulled++;
                yield $chunk;
            }
        })();

        $output = $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', $chunks));

        $this->assertFalse($output);
        // The first byte is always sent, then the response ends with the error in its trailers
        $response = $this->streamedResponse();
        $this->assertSame('Hello', $response->getBody()->__toString());
        $this->assertSame(ResponseTimedOut::class, $response->getHeaderLine('lambda-runtime-function-error-type'));
        $this->assertSame(2, $pulled);
        $this->assertErrorInLogs(ResponseTimedOut::class, (new ResponseTimedOut)->getMessage());
    }

    public function test big streamed responses are ended earlier before the timeout()
    {
        // 3 seconds left: enough for a small response, not for a big one throttled by Lambda
        $this->givenAnEvent(['Hello' => 'world!'], (int) (microtime(true) * 1000) + 3000);
        $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', ['small', ' response']));
        $this->assertSame('small response', $this->streamedResponse()->getBody()->__toString());
        Server::flush();

        $this->givenAnEvent(['Hello' => 'world!'], (int) (microtime(true) * 1000) + 3000);
        $big = str_repeat('a', 7 * 1024 * 1024);
        $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', [$big, ' end']));
        $response = $this->streamedResponse();
        $this->assertSame(strlen($big), $response->getBody()->getSize());
        $this->assertSame(ResponseTimedOut::class, $response->getHeaderLine('lambda-runtime-function-error-type'));
    }

    public function test a response produced before the timeout is sent even close to it()
    {
        $this->givenAnEvent(['Hello' => 'world!'], (int) (microtime(true) * 1000) + 500);

        $this->runtime->processNextEvent(fn () => ['Hello' => 'world!']);

        $this->assertInvocationResult(['Hello' => 'world!']);
    }

    private function streamedResponse(): RequestInterface
    {
        $requests = Server::received();
        $this->assertCount(2, $requests);
        [, $response] = $requests;
        $this->assertSame('POST', $response->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $response->getUri()->__toString());
        $this->assertSame('streaming', $response->getHeaderLine('lambda-runtime-function-response-mode'));

        return $response;
    }

    public function test errors on Function URLs are a 500 without details()
    {
        putenv('BREF_RESPONSE_STREAMING=1');
        $this->givenAnEvent(['requestContext' => ['domainName' => 'abc.lambda-url.eu-west-3.on.aws']]);

        $this->runtime->processNextEvent(function () {
            throw new RuntimeException('Secret details');
        });

        // Not the error endpoint: a streaming Function URL would show the error as the body of a 200 response
        $response = $this->streamedResponse();
        $body = $response->getBody()->__toString();
        $this->assertStringStartsWith('{"cookies":[],"statusCode":500', $body);
        $this->assertStringEndsWith(str_repeat("\0", 8) . 'Internal Server Error', $body);
        $this->assertStringNotContainsString('Secret details', $body);
        $this->assertSame('RuntimeException', $response->getHeaderLine('lambda-runtime-function-error-type'));
    }

    public function test an empty streamed response is sent()
    {
        $this->givenAnEvent(['Hello' => 'world!']);

        $output = $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', ['', '']));

        $this->assertTrue($output);
        $this->assertSame('', $this->streamedResponse()->getBody()->__toString());
    }

    public function test streamed chunks are sent intact()
    {
        $this->givenAnEvent(['Hello' => 'world!']);
        // Looks like the end of a chunked body, and binary data
        $tricky = "a\r\n0\r\n\r\nb\0c";
        // The test server records text: a big body of random text
        $big = bin2hex(random_bytes(150_000));

        $this->runtime->processNextEvent(fn () => new StreamedResponse('application/octet-stream', [$tricky, $big, $tricky]));

        $this->assertSame($tricky . $big . $tricky, $this->streamedResponse()->getBody()->__toString());
    }

    public function test a streamed response refused for its size throws a clear error()
    {
        Server::enqueue([
            new Response(200, ['lambda-runtime-aws-request-id' => '1'], '{ "Hello": "world!"}'),
            new Response(413, [], '{"errorMessage": "Exceeded maximum allowed payload size (6291556 bytes).", "errorType": "RequestEntityTooLarge"}'),
        ]);

        $output = $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', ['Hello ', 'world!']));

        $this->assertFalse($output);
        // The response was sent: no error request (the Runtime API would refuse it)
        $this->assertCount(2, Server::received());
        $this->assertErrorInLogs(ResponseTooBig::class, 'The Lambda response is too big and above the limit');
    }

    public function test the invocation after a failed stream can report its own failure()
    {
        $this->givenAnEvent(['Hello' => 'world!']);
        $this->runtime->processNextEvent(fn () => new StreamedResponse('text/plain', (function () {
            yield 'Hello';
            throw new RuntimeException('Failure in the middle');
        })()));
        Server::flush();

        // The next invocation fails before its first byte: the error endpoint is used again
        $this->givenAnEvent(['Hello' => 'world!']);
        $this->runtime->processNextEvent(function () {
            throw new RuntimeException('Failure before the first byte');
        });

        $this->assertInvocationErrorResult('RuntimeException', 'Failure before the first byte');
    }

    public function test errors on Function URLs without response streaming use the error endpoint()
    {
        $this->givenAnEvent(['requestContext' => ['domainName' => 'abc.lambda-url.eu-west-3.on.aws']]);

        $this->runtime->processNextEvent(function () {
            throw new RuntimeException('Failure');
        });

        // Like any buffered invoker: the Function URL answers an error status, without the details
        $this->assertInvocationErrorResult('RuntimeException', 'Failure');
    }

    public function test a Function URL response failing in the middle is truncated()
    {
        putenv('BREF_RESPONSE_STREAMING=1');
        $this->givenAnEvent([
            'version' => '2.0',
            'rawPath' => '/',
            'requestContext' => ['domainName' => 'abc.lambda-url.eu-west-3.on.aws', 'http' => ['method' => 'GET']],
        ]);
        $handler = new class extends HttpHandler {
            public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
            {
                return new HttpResponse((function () {
                    yield 'Hello';
                    throw new RuntimeException('Failure in the middle');
                })(), ['content-type' => 'text/plain']);
            }
        };

        $this->runtime->processNextEvent($handler);

        // The status is already sent: truncated body, the error in the trailers, no second response
        $response = $this->streamedResponse();
        $body = $response->getBody()->__toString();
        $this->assertStringStartsWith('{"cookies":[],"statusCode":200', $body);
        $this->assertStringEndsWith(str_repeat("\0", 8) . 'Hello', $body);
        $this->assertSame(1, substr_count($body, '"statusCode"'));
        $this->assertSame('RuntimeException', $response->getHeaderLine('lambda-runtime-function-error-type'));
    }

    public function test an error is thrown if the runtime API returns a wrong response()
    {
        $this->expectExceptionMessageMatches('/Failed to fetch next Lambda invocation: The requested URL returned error: 404/');
        Server::enqueue([
            new Response( // lambda event
                404, // 404 instead of 200
                [
                    'lambda-runtime-aws-request-id' => '1',
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
                    'lambda-runtime-aws-request-id' => '1',
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
                    'lambda-runtime-aws-request-id' => '1',
                ],
                '{ "Hello": "world!"}'
            ),
            new Response(
                400, // The Lambda API returns a 400 instead of a 200
                [],
                // The Lambda API returns a JSON response with "errorMessage" and "errorType"
                '{
                    "errorMessage": "Fake exception message.",
                    "errorType": "FakeException"
                }',
            ),
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

        // Check the lambda result contains the error message
        $error = json_decode((string) $eventFailureLog->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertStringContainsString('Error 400 while calling the Lambda runtime API: FakeException: Fake exception message.', $error['errorMessage']);

        $this->assertErrorInLogs('Exception', 'Error 400 while calling the Lambda runtime API: FakeException: Fake exception message.');
    }

    /**
     * Special test for 413 because we want to show a specific error message (it might be hitting the 6MB limit)
     * and we want to skip reporting the error to the Lambda API.
     */
    public function test a 413 response from the runtime API throws a clear error()
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => '1',
                ],
                '{ "Hello": "world!"}'
            ),
            new Response(
                413, // The Lambda API returns a 403 instead of a 200
                [],
                '{
                    "errorMessage": "Exceeded maximum allowed payload size (6291556 bytes).",
                    "errorType": "RequestEntityTooLarge"
                }',
            ),
        ]);

        $this->runtime->processNextEvent(function ($event) {
            return $event;
        });
        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventFailureResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventFailureResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventFailureResponse->getUri()->__toString());

        $this->assertErrorInLogs(ResponseTooBig::class, 'The Lambda response is too big and above the limit');
    }

    public function test function results that cannot be encoded are reported as invocation errors()
    {
        $this->givenAnEvent(['hello' => 'world!']);

        $this->runtime->processNextEvent(function () {
            return "\xB1\x31";
        });

        $message = <<<ERROR
The Lambda response cannot be encoded to JSON.
This error usually happens when you try to return binary content. If you are writing an HTTP application and you want to return a binary HTTP response (like an image, a PDF, etc.), please read this guide: https://bref.sh/docs/runtimes/http.html#binary-requests-and-responses
Here is the original JSON error: 'Malformed UTF-8 characters, possibly incorrectly encoded'
ERROR;
        $this->assertInvocationErrorResult('Exception', $message);
        $this->assertErrorInLogs('Exception', $message);
    }

    public function test generic event handler()
    {
        $handler = new class() implements Handler {
            public function handle(mixed $event, Context $context): mixed
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
        $handler = new class() extends SqsHandler {
            public SqsEvent $event;
            public function handleSqs(SqsEvent $event, Context $context): void
            {
                $this->event = $event;
            }
        };

        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/Sqs/sqs.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent($handler);

        $this->assertEquals(new SqsEvent($eventData), $handler->event);
    }

    public function test SNS event handler()
    {
        $handler = new class() extends SnsHandler {
            public SnsEvent $event;
            public function handleSns(SnsEvent $event, Context $context): void
            {
                $this->event = $event;
            }
        };

        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/Sns/sns.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent($handler);

        $this->assertEquals(new SnsEvent($eventData), $handler->event);
    }

    public function test S3 event handler()
    {
        $handler = new class() extends S3Handler {
            public S3Event $event;
            public function handleS3(S3Event $event, Context $context): void
            {
                $this->event = $event;
            }
        };

        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/S3/s3.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent($handler);

        $this->assertEquals(new S3Event($eventData), $handler->event);
    }

    public function test PSR15 event handler()
    {
        $handler = new class() implements RequestHandlerInterface {
            public ServerRequestInterface $request;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->request = $request;
                return new Response(200, [
                    'Content-Type' => 'text/html',
                ], 'Hello world!');
            }
        };

        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/Http/Fixture/ag-v1-simple.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent($handler);

        $this->assertEquals('GET', $handler->request->getMethod());
        $this->assertEquals('/path', (string) $handler->request->getUri());
        $this->assertInstanceOf(HttpRequestEvent::class, $handler->request->getAttribute('lambda-event'));
        $this->assertInstanceOf(Context::class, $handler->request->getAttribute('lambda-context'));
        $this->assertInvocationResult([
            'isBase64Encoded' => false,
            'statusCode' => 200,
            'headers' => [
                'Content-Type' => 'text/html',
            ],
            'body' => 'Hello world!',
        ]);
    }

    public function test EventBridge event handler()
    {
        $handler = new class() extends EventBridgeHandler {
            public EventBridgeEvent $event;
            public function handleEventBridge(EventBridgeEvent $event, Context $context): void
            {
                $this->event = $event;
            }
        };

        $eventData = json_decode(file_get_contents(__DIR__ . '/../Event/EventBridge/eventbridge.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->givenAnEvent($eventData);

        $this->runtime->processNextEvent($handler);

        $this->assertEquals(new EventBridgeEvent($eventData), $handler->event);
    }

    public function test exceptions in beforeInvoke result in an invocation error()
    {
        Bref::events()->subscribe(new class extends BrefEventSubscriber {
            public function beforeInvoke(mixed ...$params): void
            {
                throw new Exception('This is an exception in beforeInvoke');
            }
        });

        $this->givenAnEvent([]);

        $output = $this->runtime->processNextEvent(fn () => []);

        $this->assertFalse($output);
        $this->assertInvocationErrorResult('Exception', 'This is an exception in beforeInvoke');
        $this->assertErrorInLogs('Exception', 'This is an exception in beforeInvoke');
    }

    /**
     * Once we reported to Lambda that the execution was successful, we should not report a failure.
     * So any exception in `afterInvoke` should not be reported as a failure.
     */
    public function test a failure in afterInvoke after a success does not signal a failure()
    {
        Bref::events()->subscribe(new class extends BrefEventSubscriber {
            public function afterInvoke(mixed ...$params): void
            {
                throw new Exception('This is an exception in afterInvoke');
            }
        });

        $this->givenAnEvent([]);
        $output = $this->runtime->processNextEvent(fn () => []);

        $this->assertFalse($output);
        $this->assertErrorInLogs('Exception', 'This is an exception in afterInvoke');
        $requests = Server::received();
        // Only the event request and the success response should be sent, no error should be sent
        $this->assertCount(2, $requests);
        [$eventRequest, $eventSuccessResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventSuccessResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventSuccessResponse->getUri()->__toString());
    }

    public function test a failure in afterInvoke after a failure does not crash the runtime()
    {
        Bref::events()->subscribe(new class extends BrefEventSubscriber {
            public function afterInvoke(mixed ...$params): void
            {
                throw new Exception('This is an exception in afterInvoke');
            }
        });

        $this->givenAnEvent([]);
        $output = $this->runtime->processNextEvent(fn () => throw new Exception('Invocation error'));

        $this->assertFalse($output);
        // The error response was already sent, it contains the handler error
        $this->assertInvocationErrorResult('Exception', 'Invocation error');
        // The logs should contain both the handler error and the afterInvoke error
        $this->assertStringContainsString('Invoke Error	{"errorType":"Exception","errorMessage":"Invocation error","stack":', $this->output());
        $this->assertStringContainsString('Invoke Error	{"errorType":"Exception","errorMessage":"This is an exception in afterInvoke","stack":', $this->output());
    }

    public function test request id env variables()
    {
        $this->givenAnEvent([]);

        $this->runtime->processNextEvent(function () use (&$requestId, &$traceId) {
            $requestId = getenv('LAMBDA_REQUEST_ID');
            $traceId = getenv('_X_AMZN_TRACE_ID');
            return ['hello' => 'world'];
        });

        $this->assertSame('1', $requestId);
        $this->assertSame('Root=1-67891233-abcdef012345678912345678', $traceId);
    }
}
