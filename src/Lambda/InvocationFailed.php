<?php declare(strict_types=1);

namespace Bref\Lambda;

final class InvocationFailed extends \Exception
{
    /** @var InvocationResult */
    private $invocationResult;

    public function __construct(InvocationResult $invocationResult)
    {
        $this->invocationResult = $invocationResult;
        $message = $invocationResult->getPayload()['errorMessage'] ?? 'Unknown error';

        parent::__construct($message);
    }

    public function getInvocationLogs(): string
    {
        return $this->invocationResult->getLogs();
    }
}
