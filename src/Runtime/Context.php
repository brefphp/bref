<?php declare(strict_types=1);

namespace Bref\Runtime;

class Context
{
    /** @var string */
    private $awsRequestId = '';

    /** @var int */
    private $deadlineMs;

    /** @var string */
    private $invokedFunctionArn;

    /** @var string */
    private $traceId;

    public function getAwsRequestId(): string
    {
        return $this->awsRequestId;
    }

    public function setAwsRequestId(string $awsRequestId): Context
    {
        $this->awsRequestId = $awsRequestId;
        return $this;
    }

    public function getDeadlineMs(): int
    {
        return $this->deadlineMs;
    }

    public function setDeadlineMs(int $deadlineMs): Context
    {
        $this->deadlineMs = $deadlineMs;
        return $this;
    }

    public function getInvokedFunctionArn(): string
    {
        return $this->invokedFunctionArn;
    }

    public function setInvokedFunctionArn(string $invokedFunctionArn): Context
    {
        $this->invokedFunctionArn = $invokedFunctionArn;
        return $this;
    }

    public function getTraceId(): string
    {
        return $this->traceId;
    }

    public function setTraceId(string $traceId): Context
    {
        $this->traceId = $traceId;
        return $this;
    }
}
