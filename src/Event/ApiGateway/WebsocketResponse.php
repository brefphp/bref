<?php declare(strict_types=1);

namespace Bref\Event\ApiGateway;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 */
final class WebsocketResponse
{
    /** @var int */
    private $statusCode;

    public function __construct(int $statusCode = 200)
    {
        $this->statusCode = $statusCode;
    }

    public function toApiGatewayFormat(): array
    {
        return [
            'statusCode' => $this->statusCode,
        ];
    }
}
