<?php declare(strict_types=1);

namespace Bref\Runtime;

class Event
{
    /** @var mixed */
    private $invocationId;

    /** @var array */
    private $data;

    /** @var Client */
    private $client;

    public function __construct($invocationId, array $data, Client $client)
    {
        $this->invocationId = $invocationId;
        $this->data = $data;
        $this->client = $client;
    }

    public function data(): array
    {
        return $this->data;
    }

    /**
     * @param mixed $response
     */
    public function fulfill($response): void
    {
        $this->client->sendResponse($this->invocationId, $response);
    }

    /**
     * @param mixed $response
     */
    public function reject($response)
    {
        $this->client->signalFailure($this->invocationId, $response);
    }
}
