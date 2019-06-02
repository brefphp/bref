<?php declare(strict_types=1);

namespace Bref\Lambda;

use Aws\Result;

/**
 * The result of a successful lambda invocation.
 */
final class InvocationResult
{
    /** @var Result */
    private $result;

    /** @var mixed */
    private $payload;

    /**
     * @param mixed $payload
     */
    public function __construct(Result $result, $payload)
    {
        $this->result = $result;
        $this->payload = $payload;
    }

    public function getLogs(): string
    {
        return base64_decode($this->result->get('LogResult'));
    }

    /**
     * @return mixed
     */
    public function getPayload()
    {
        return $this->payload;
    }
}
