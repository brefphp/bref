<?php declare(strict_types=1);

namespace Bref\Runtime;

/**
 * Client for the AWS Lambda runtime API.
 *
 * This allows to interact with the API and:
 *
 * - fetch events to process
 * - signal errors
 * - send invocation responses
 *
 * It is intentionally dependency-free to keep cold starts as low as possible.
 *
 * Usage example:
 *
 *     $lambdaRuntime = LambdaRuntime::fromEnvironmentVariable();
 *     $lambdaRuntime->processNextEvent(function ($event) {
 *         return <response>;
 *     });
 */
class LambdaRuntime
{
    /** @var Client */
    private $client;

    public static function fromEnvironmentVariable(): self
    {
        return new self(
            new Client(getenv('AWS_LAMBDA_RUNTIME_API'))
        );
    }

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Process the next event.
     *
     * @param callable $handler This callable takes a $event parameter (array) and must return anything serializable to JSON.
     *
     * Example:
     *
     *     $lambdaRuntime->processNextEvent(function (array $event) {
     *         return 'Hello ' . $event['name'];
     *     });
     */
    public function processNextEvent(callable $handler): void
    {
        [$invocationId, $event] = $this->client->waitNextInvocation();

        try {
            $this->client->sendResponse($invocationId, $handler($event));
        } catch (\Throwable $e) {
            $this->client->signalFailure($invocationId, $e);
        }
    }

    public function fetchNextEvent(): Event
    {
        [$invocationId, $event] = $this->client->waitNextInvocation();

        return new Event($invocationId, $event, $this->client);
    }
}
