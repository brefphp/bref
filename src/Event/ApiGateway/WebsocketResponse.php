<?php declare(strict_types=1);

namespace Bref\Event\ApiGateway;

use RuntimeException;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 */
final class WebsocketResponse
{
    /** @var int */
    private $statusCode;

    /** @var string[]|null */
    private $protocol = null;

    /**
     * @param string|string[] $protocol
     */
    public function __construct(int $statusCode = 200, $protocol = null)
    {
        $this->statusCode = $statusCode;

        if ($protocol !== null) {
            if (! is_string($protocol) && ! is_array($protocol)) {
                throw new RuntimeException('$protocol has to be of type: string|string[]');
            }

            $this->protocol = ! is_array($protocol) ? [$protocol] : $protocol;
        }
    }

    public function toApiGatewayFormat(): array
    {
        // This is the format required by ApiGateway
        // See https://docs.aws.amazon.com/apigateway/latest/developerguide/websocket-connect-route-subprotocol.html
        $response = [
            'statusCode' => $this->statusCode,
        ];

        if ($this->protocol !== null) {
            $response['headers'] = [
                'Sec-WebSocket-Protocol' => implode(', ', $this->protocol),
            ];
        }

        return $response;
    }
}
