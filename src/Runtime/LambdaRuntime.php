<?php declare(strict_types=1);

namespace Bref\Runtime;

use Bref\Handler\ContextAwareHandler;

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
    /** @var resource|null */
    private $handler;

    /** @var resource|null */
    private $returnHandler;

    /** @var string */
    private $apiUrl;

    public static function fromEnvironmentVariable(): self
    {
        return new self(getenv('AWS_LAMBDA_RUNTIME_API'));
    }

    public function __construct(string $apiUrl)
    {
        if ($apiUrl === '') {
            die('At the moment lambdas can only be executed in an Lambda environment');
        }

        $this->apiUrl = $apiUrl;
    }

    public function __destruct()
    {
        $this->closeHandler();
        $this->closeReturnHandler();
    }

    private function closeHandler(): void
    {
        if ($this->handler !== null) {
            curl_close($this->handler);
            $this->handler = null;
        }
    }
    private function closeReturnHandler(): void
    {
        if ($this->returnHandler !== null) {
            curl_close($this->returnHandler);
            $this->returnHandler = null;
        }
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
     *
     * The callable can also be an invokable class that implements ContextAwareHandler interface. In that case it will
     * also receive a second argument which contains the execution context that can provide extra information such as
     * invocationId and other headers returned by AWS Runtime API
     * @throws \Exception
     */
    public function processNextEvent(callable $handler): void
    {
        /** @var Context $context */
        [$event, $context] = $this->waitNextInvocation();

        try {
            if ($handler instanceof ContextAwareHandler) {
                $this->sendResponse($context->getAwsRequestId(), $handler($event, $context));
            } else {
                $this->sendResponse($context->getAwsRequestId(), $handler($event));
            }
        } catch (\Throwable $e) {
            $this->signalFailure($context->getAwsRequestId(), $e);
        }
    }

    /**
     * Wait for the next lambda invocation and retrieve its data.
     *
     * This call is blocking because the Lambda runtime API is blocking.
     *
     * @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html#runtimes-api-next
     */
    private function waitNextInvocation(): array
    {
        if ($this->handler === null) {
            $this->handler = curl_init("http://{$this->apiUrl}/2018-06-01/runtime/invocation/next");
            curl_setopt($this->handler, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($this->handler, CURLOPT_FAILONERROR, true);
        }

        // Retrieve invocation ID
        $context = new Context;
        curl_setopt($this->handler, CURLOPT_HEADERFUNCTION, function ($ch, $header) use ($context) {
            if (! preg_match('/:\s*/', $header)) {
                return strlen($header);
            }
            [$name, $value] = preg_split('/:\s*/', $header, 2);
            if (strtolower($name) === 'lambda-runtime-aws-request-id') {
                $context->setAwsRequestId(trim($value));
            }
            if (strtolower($name) === 'lambda-runtime-deadline-ms') {
                $context->setDeadlineMs(intval(trim($value)));
            }
            if (strtolower($name) === 'lambda-runtime-invoked-function-arn') {
                $context->setInvokedFunctionArn(trim($value));
            }
            if (strtolower($name) === 'lambda-runtime-trace-id') {
                $context->setTraceId(trim($value));
            }

            return strlen($header);
        });

        // Retrieve body
        $body = '';
        curl_setopt($this->handler, CURLOPT_WRITEFUNCTION, function ($ch, $chunk) use (&$body) {
            $body .= $chunk;

            return strlen($chunk);
        });

        curl_exec($this->handler);
        if (curl_errno($this->handler) > 0) {
            $message = curl_error($this->handler);
            $this->closeHandler();
            throw new \Exception('Failed to fetch next Lambda invocation: ' . $message);
        }
        if ($context->getAwsRequestId() === '') {
            throw new \Exception('Failed to determine the Lambda invocation ID');
        }
        if ($body === '') {
            throw new \Exception('Empty Lambda runtime API response');
        }

        $event = json_decode($body, true);

        return [$event, $context];
    }

    /**
     * @param mixed $responseData
     *
     * @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html#runtimes-api-response
     */
    private function sendResponse(string $invocationId, $responseData): void
    {
        $url = "http://{$this->apiUrl}/2018-06-01/runtime/invocation/$invocationId/response";
        $this->postJson($url, $responseData);
    }

    /**
     * @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html#runtimes-api-invokeerror
     */
    private function signalFailure(string $invocationId, \Throwable $error): void
    {
        if ($error instanceof \Exception) {
            $errorMessage = 'Uncaught ' . get_class($error) . ': ' . $error->getMessage();
        } else {
            $errorMessage = $error->getMessage();
        }

        // Log the exception in CloudWatch
        printf(
            "Fatal error: %s in %s:%d\nStack trace:\n%s",
            $errorMessage,
            $error->getFile(),
            $error->getLine(),
            $error->getTraceAsString()
        );

        // Send an "error" Lambda response
        $url = "http://{$this->apiUrl}/2018-06-01/runtime/invocation/$invocationId/error";
        $this->postJson($url, [
            'errorMessage' => $error->getMessage(),
            'errorType' => get_class($error),
            'stackTrace' => explode(PHP_EOL, $error->getTraceAsString()),
        ]);
    }

    /**
     * Abort the lambda and signal to the runtime API that we failed to initialize this instance.
     *
     * @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html#runtimes-api-initerror
     */
    public function failInitialization(string $message, ?\Throwable $error = null): void
    {
        // Log the exception in CloudWatch
        echo "$message\n";
        if ($error) {
            if ($error instanceof \Exception) {
                $errorMessage = get_class($error) . ': ' . $error->getMessage();
            } else {
                $errorMessage = $error->getMessage();
            }
            printf(
                "Fatal error: %s in %s:%d\nStack trace:\n%s",
                $errorMessage,
                $error->getFile(),
                $error->getLine(),
                $error->getTraceAsString()
            );
        }

        $url = "http://{$this->apiUrl}/2018-06-01/runtime/init/error";
        $this->postJson($url, [
            'errorMessage' => $message . ' ' . ($error ? $error->getMessage() : ''),
            'errorType' => $error ? get_class($error) : 'Internal',
            'stackTrace' => $error ? explode(PHP_EOL, $error->getTraceAsString()) : [],
        ]);

        exit(1);
    }

    /**
     * @param mixed $data
     */
    private function postJson(string $url, $data): void
    {
        $jsonData = json_encode($data);
        if ($jsonData === false) {
            throw new \Exception('Failed encoding Lambda JSON response: ' . json_last_error_msg());
        }

        if ($this->returnHandler === null) {
            $this->returnHandler = curl_init();
            curl_setopt($this->returnHandler, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($this->returnHandler, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($this->returnHandler, CURLOPT_FAILONERROR, true);
        }

        curl_setopt($this->returnHandler, CURLOPT_URL, $url);
        curl_setopt($this->returnHandler, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($this->returnHandler, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($jsonData),
        ]);
        curl_exec($this->returnHandler);
        if (curl_errno($this->returnHandler) > 0) {
            $errorMessage = curl_error($this->returnHandler);
            $this->closeReturnHandler();
            throw new \Exception('Error while calling the Lambda runtime API: ' . $errorMessage);
        }
    }
}
