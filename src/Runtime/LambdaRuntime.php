<?php declare(strict_types=1);

namespace Bref\Runtime;

use Bref\Context\Context;
use Bref\Context\ContextBuilder;

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
 *
 * @internal
 */
final class LambdaRuntime
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
     * @param callable $handler This callable takes two parameters, an $event parameter (array) and a $context parameter (Context) and must return anything serializable to JSON.
     *
     * Example:
     *
     *     $lambdaRuntime->processNextEvent(function (array $event, Context $context) {
     *         return 'Hello ' . $event['name'] . '. We have ' . $context->getRemainingTimeInMillis()/1000 . ' seconds left';
     *     });
     * @throws \Exception
     */
    public function processNextEvent(callable $handler): void
    {
        /** @var Context $context */
        [$event, $context] = $this->waitNextInvocation();

        try {
            $this->sendResponse($context->getAwsRequestId(), $handler($event, $context));
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
        $contextBuilder = new ContextBuilder;
        curl_setopt($this->handler, CURLOPT_HEADERFUNCTION, function ($ch, $header) use ($contextBuilder) {
            if (! preg_match('/:\s*/', $header)) {
                return strlen($header);
            }
            [$name, $value] = preg_split('/:\s*/', $header, 2);
            $name = strtolower($name);
            $value = trim($value);
            if ($name === 'lambda-runtime-aws-request-id') {
                $contextBuilder->setAwsRequestId($value);
            }
            if ($name === 'lambda-runtime-deadline-ms') {
                $contextBuilder->setDeadlineMs(intval($value));
            }
            if ($name === 'lambda-runtime-invoked-function-arn') {
                $contextBuilder->setInvokedFunctionArn($value);
            }
            if ($name === 'lambda-runtime-trace-id') {
                $contextBuilder->setTraceId($value);
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
        if ($body === '') {
            throw new \Exception('Empty Lambda runtime API response');
        }

        $context = $contextBuilder->buildContext();

        if ($context->getAwsRequestId() === '') {
            throw new \Exception('Failed to determine the Lambda invocation ID');
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
        $contentType = $data['multiValueHeaders']['content-type'][0] ?? null;
        if ($contentType && $contentType !== 'application/json' && substr($contentType, 0, 4) !== 'text') {
            $data['body'] = base64_encode($data['body']);
            $data['isBase64Encoded'] = true;
        }

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
