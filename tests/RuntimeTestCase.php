<?php declare(strict_types=1);

namespace Bref\Test;

use Bref\Bref;
use GuzzleHttp\Psr7\Response;
use JsonException;
use PHPUnit\Framework\TestCase;

/**
 * The Lambda Runtime HTTP API is mocked using a fake HTTP server.
 */
abstract class RuntimeTestCase extends TestCase
{
    protected function setUp(): void
    {
        Bref::reset();
        ob_start();
        Server::start();
        putenv('AWS_LAMBDA_RUNTIME_API=localhost:8126');
    }

    protected function tearDown(): void
    {
        Server::stop();
        ob_end_clean();
    }

    protected function givenAnEvent(mixed $event): void
    {
        Server::enqueue([
            new Response( // lambda event
                200,
                [
                    'lambda-runtime-aws-request-id' => '1',
                    'lambda-runtime-invoked-function-arn' => 'test-function-name',
                ],
                json_encode($event, JSON_THROW_ON_ERROR)
            ),
            new Response(200), // lambda response accepted
        ]);
    }

    protected function assertInvocationResult(mixed $result): void
    {
        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/response', $eventResponse->getUri()->__toString());
        $this->assertEquals($result, json_decode($eventResponse->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR));
    }

    protected function assertInvocationErrorResult(string $errorClass, string $errorMessage): void
    {
        $requests = Server::received();
        $this->assertCount(2, $requests);

        [$eventRequest, $eventResponse] = $requests;
        $this->assertSame('GET', $eventRequest->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/next', $eventRequest->getUri()->__toString());
        $this->assertSame('POST', $eventResponse->getMethod());
        $this->assertSame('http://localhost:8126/2018-06-01/runtime/invocation/1/error', $eventResponse->getUri()->__toString());

        // Check the content of the result of the lambda
        $invocationResult = json_decode($eventResponse->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame([
            'errorType',
            'errorMessage',
            'stackTrace',
        ], array_keys($invocationResult));
        $this->assertEquals($errorClass, $invocationResult['errorType']);
        $this->assertEquals($errorMessage, $invocationResult['errorMessage']);
        $this->assertIsArray($invocationResult['stackTrace']);
    }

    protected function assertErrorInLogs(string $errorClass, string $errorMessage): void
    {
        // Decode the logs from stdout
        $stdout = $this->getActualOutput();

        [$requestId, $message, $json] = explode("\t", $stdout);

        $this->assertSame('Invoke Error', $message);

        // Check the request ID matches a UUID
        $this->assertNotEmpty($requestId);

        try {
            $invocationResult = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->fail("Could not decode JSON from logs ({$e->getMessage()}): $json");
        }
        unset($invocationResult['previous']);
        $this->assertSame([
            'errorType',
            'errorMessage',
            'stack',
        ], array_keys($invocationResult));
        $this->assertEquals($errorClass, $invocationResult['errorType']);
        $this->assertStringContainsString($errorMessage, $invocationResult['errorMessage']);
        $this->assertIsArray($invocationResult['stack']);
    }

    protected function assertPreviousErrorsInLogs(array $previousErrors): void
    {
        // Decode the logs from stdout
        $stdout = $this->getActualOutput();

        [, , $json] = explode("\t", $stdout);

        ['previous' => $previous] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(count($previousErrors), $previous);
        foreach ($previous as $index => $error) {
            $this->assertSame([
                'errorType',
                'errorMessage',
                'stack',
            ], array_keys($error));
            $this->assertEquals($previousErrors[$index]['errorClass'], $error['errorType']);
            $this->assertEquals($previousErrors[$index]['errorMessage'], $error['errorMessage']);
            $this->assertIsArray($error['stack']);
        }
    }
}
