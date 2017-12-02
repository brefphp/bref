<?php
declare(strict_types=1);

namespace PhpLambda;

use Psr\Http\Message\ResponseInterface;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class LambdaResponse
{
    /**
     * @var int
     */
    private $statusCode = 200;

    /**
     * @var array
     */
    private $headers;

    /**
     * @var string
     */
    private $body;

    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
    }

    public static function fromPsr7Response(ResponseInterface $response) : self
    {
        // The lambda proxy integration does not support arrays in headers
        $headers = array_map(function ($header) {
            if (is_array($header)) {
                return $header[0];
            }
            return $header;
        }, $response->getHeaders());

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        return new self($response->getStatusCode(), $headers, $body);
    }

    public function toJson() : string
    {
        // This is the format required by the AWS_PROXY lambda integration
        // See https://stackoverflow.com/questions/43708017/aws-lambda-api-gateway-error-malformed-lambda-proxy-response
        return json_encode([
            'isBase64Encoded' => false,
            'statusCode' => $this->statusCode,
            'headers' => $this->headers,
            'body' => $this->body,
        ]);
    }
}
