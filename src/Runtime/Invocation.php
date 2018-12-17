<?php declare(strict_types=1);

namespace Bref\Runtime;

/**
 * Represents a Lambda invocation.
 */
class Invocation
{
    /** @var LambdaRuntime */
    private $runtime;

    /** @var string */
    private $id;

    /** @var array */
    private $event;

    public function __construct(LambdaRuntime $runtime, string $id, array $event)
    {
        $this->runtime = $runtime;
        $this->id = $id;
        $this->event = $event;
    }

    /**
     * Event data sent by the invoker.
     */
    public function getEvent(): array
    {
        return $this->event;
    }

    /**
     * Call this when the invocation has succeeded.
     *
     * @param mixed $responseData
     */
    public function success($responseData = null): void
    {
        $this->runtime->sendResponse($this->id, $responseData);
    }

    /**
     * Call this when the invocation has failed because of an unrecoverable error.
     */
    public function failure(\Throwable $error): void
    {
        $this->runtime->signalFailure($this->id, $error);
    }
}
