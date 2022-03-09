<?php declare(strict_types=1);

namespace Bref\Context;

/**
 * The execution context of a Lambda.
 *
 * @see https://docs.aws.amazon.com/lambda/latest/dg/nodejs-prog-model-context.html
 */
final class Context implements \JsonSerializable
{
    /** @var string */
    private $awsRequestId;

    /** @var int Holds the deadline Unix timestamp in millis */
    private $deadlineMs;

    /** @var string */
    private $invokedFunctionArn;

    /** @var string */
    private $traceId;

    /** @var null|self  */
    private static $instance = null;

    public function __construct(string $awsRequestId, int $deadlineMs, string $invokedFunctionArn, string $traceId)
    {
        $this->awsRequestId = $awsRequestId;
        $this->deadlineMs = $deadlineMs;
        $this->invokedFunctionArn = $invokedFunctionArn;
        $this->traceId = $traceId;
    }

    /**
     * Returns the identifier of the invocation request.
     */
    public function getAwsRequestId(): string
    {
        return $this->awsRequestId;
    }

    /**
     * Returns the number of milliseconds left before the execution times out.
     */
    public function getRemainingTimeInMillis(): int
    {
        return $this->deadlineMs - intval(microtime(true) * 1000);
    }

    /**
     * Returns the Amazon Resource Name (ARN) used to invoke the function.
     * Indicates if the invoker specified a version number or alias.
     */
    public function getInvokedFunctionArn(): string
    {
        return $this->invokedFunctionArn;
    }

    /**
     * Returns content of the AWS X-Ray trace information header
     *
     * @see  https://docs.aws.amazon.com/xray/latest/devguide/xray-concepts.html#xray-concepts-tracingheader
     */
    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function jsonSerialize(): array
    {
        return [
            'awsRequestId' => $this->awsRequestId,
            'deadlineMs' => $this->deadlineMs,
            'invokedFunctionArn' => $this->invokedFunctionArn,
            'traceId' => $this->traceId,
        ];
    }

    /**
     * Bref will make the Context globally available as soon as a new Invocation is retrieved.
     *
     * @internal
     */
    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    /**
     * Provide a null-safe Context statically for users. If this method is called during cold start
     * bootstrapping or after an invocation has been finished, it will return null.
     */
    public static function getInstance(): ?self
    {
        if (self::$instance) {
            return self::$instance;
        }

        return null;
    }

    /**
     * Provide the current Context globally for users. Calling this method
     * before an invocation event has been retrieved will result in
     * a fatal error.
     */
    public static function current(): self
    {
        return self::getInstance();
    }

    /**
     * Bref will make sure to not leave global Context hanging around stale as to not
     * run the chance of mixing it between two invocations.
     *
     * @internal
     */
    public static function flush(): void
    {
        self::$instance = null;
    }
}
