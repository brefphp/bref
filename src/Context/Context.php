<?php declare(strict_types=1);

namespace Bref\Context;

use JsonSerializable;

/**
 * The execution context of a Lambda.
 *
 * @see https://docs.aws.amazon.com/lambda/latest/dg/nodejs-prog-model-context.html
 */
final class Context implements JsonSerializable
{
    /**
     * @param int $deadlineMs Holds the deadline Unix timestamp in millis
     */
    public function __construct(
        private string $awsRequestId,
        private int $deadlineMs,
        private string $invokedFunctionArn,
        private string $traceId
    ) {
    }

    /**
     * Test helper to create a fake context in one line.
     */
    public static function fake(): self
    {
        return new self(
            'fake-aws-request-id',
            (time() + (60 * 5)) * 1000, // 5 minutes from now (in milliseconds)
            'fake-invoked-function-arn',
            'fake-trace-id'
        );
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
}
