<?php declare(strict_types=1);

namespace Bref\Context;

final class ContextBuilder
{
    private string $awsRequestId;
    private int $deadlineMs;
    private string $invokedFunctionArn;
    private string $traceId;

    public function __construct()
    {
        $this->awsRequestId = '';
        $this->deadlineMs = 0;
        $this->invokedFunctionArn = '';
        $this->traceId = '';
    }

    public function setAwsRequestId(string $awsRequestId): void
    {
        $this->awsRequestId = $awsRequestId;
    }

    public function setDeadlineMs(int $deadlineMs): void
    {
        $this->deadlineMs = $deadlineMs;
    }

    public function setInvokedFunctionArn(string $invokedFunctionArn): void
    {
        $this->invokedFunctionArn = $invokedFunctionArn;
    }

    public function setTraceId(string $traceId): void
    {
        $this->traceId = $traceId;
    }

    public function buildContext(): Context
    {
        return new Context(
            $this->awsRequestId,
            $this->deadlineMs,
            $this->invokedFunctionArn,
            $this->traceId
        );
    }
}
