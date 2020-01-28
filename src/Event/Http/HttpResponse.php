<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 *
 * @internal
 */
final class HttpResponse
{
    /** @var int */
    private $statusCode;

    /** @var array */
    private $headers;

    /** @var string */
    private $body;

    public function __construct(string $body, array $headers, int $statusCode = 200)
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    public static function fromPsr7Response(ResponseInterface $response): self
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

        return new self($body, $headers, $response->getStatusCode());
    }

    public function toApiGatewayFormat(bool $multiHeaders = false): array
    {
        $base64Encoding = (bool) getenv('BREF_BINARY_RESPONSES');

        // The headers must be a JSON object. If the PHP array is empty it is
        // serialized to `[]` (we want `{}`) so we force it to an empty object.
        $headers = empty($this->headers) ? new \stdClass : $this->headers;

        // Support for multi-value headers
        $headersKey = $multiHeaders ? 'multiValueHeaders' : 'headers';
        if ($multiHeaders) {
            $headers = array_map(function ($value): array {
                return is_array($value) ? $value : [$value];
            }, $headers);
        }

        // This is the format required by the AWS_PROXY lambda integration
        // See https://stackoverflow.com/questions/43708017/aws-lambda-api-gateway-error-malformed-lambda-proxy-response
        return [
            'isBase64Encoded' => $base64Encoding,
            'statusCode' => $this->statusCode,
            $headersKey => $headers,
            'body' => $base64Encoding ? base64_encode($this->body) : $this->body,
        ];
    }
}
