<?php
declare(strict_types=1);

namespace Bref\Http;

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
        $headers = [];
        foreach ($response->getHeaders() as $name => $values) {
            // See https://github.com/zendframework/zend-diactoros/blob/754a2ceb7ab753aafe6e3a70a1fb0370bde8995c/src/Response/SapiEmitterTrait.php#L96
            $name = str_replace('-', ' ', $name);
            $name = ucwords($name);
            $name = str_replace(' ', '-', $name);
            foreach ($values as $value) {
                $headers[$name] = $value;
            }
        }

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        return new self($response->getStatusCode(), $headers, $body);
    }

    public static function fromHtml(string $html) : self
    {
        return new self(
            200,
            [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
            $html
        );
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
