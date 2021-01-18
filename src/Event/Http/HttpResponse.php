<?php declare(strict_types=1);

namespace Bref\Event\Http;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 */
final class HttpResponse
{
    /** @var int */
    private $statusCode;

    /** @var array */
    private $headers;

    /** @var string */
    private $body;

    /**
     * @param array<string|string[]> $headers
     */
    public function __construct(string $body, array $headers = [], int $statusCode = 200)
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    public function toApiGatewayFormat(bool $multiHeaders = false): array
    {
        $base64Encoding = (bool) getenv('BREF_BINARY_RESPONSES');

        $headers = [];
        foreach ($this->headers as $name => $values) {
            $name = $this->capitalizeHeaderName($name);

            if ($multiHeaders) {
                // Make sure the values are always arrays
                $headers[$name] = is_array($values) ? $values : [$values];
            } else {
                // Make sure the values are never arrays
                $headers[$name] = is_array($values) ? end($values) : $values;
            }
        }

        // The headers must be a JSON object. If the PHP array is empty it is
        // serialized to `[]` (we want `{}`) so we force it to an empty object.
        $headers = empty($headers) ? new \stdClass : $headers;

        // Support for multi-value headers (only in version 1.0 of the http payload)
        $headersKey = $multiHeaders ? 'multiValueHeaders' : 'headers';

        // This is the format required by the AWS_PROXY lambda integration
        // See https://stackoverflow.com/questions/43708017/aws-lambda-api-gateway-error-malformed-lambda-proxy-response
        return [
            'isBase64Encoded' => $base64Encoding,
            'statusCode' => $this->statusCode,
            $headersKey => $headers,
            'body' => $base64Encoding ? base64_encode($this->body) : $this->body,
        ];
    }

    /**
     * See https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html#http-api-develop-integrations-lambda.response
     */
    public function toApiGatewayFormatV2(): array
    {
        $base64Encoding = (bool) getenv('BREF_BINARY_RESPONSES');

        $headers = [];
        $cookies = [];
        foreach ($this->headers as $name => $values) {
            $name = $this->capitalizeHeaderName($name);

            if ($name === 'Set-Cookie') {
                $cookies = is_array($values) ? $values : [$values];
            } else {
                // Make sure the values are never arrays
                // because API Gateway v2 does not support multi-value headers
                $headers[$name] = is_array($values) ? end($values) : $values;
            }
        }

        // The headers must be a JSON object. If the PHP array is empty it is
        // serialized to `[]` (we want `{}`) so we force it to an empty object.
        $headers = empty($headers) ? new \stdClass : $headers;

        return [
            'cookies' => $cookies,
            'isBase64Encoded' => $base64Encoding,
            'statusCode' => $this->statusCode,
            'headers' => $headers,
            'body' => $base64Encoding ? base64_encode($this->body) : $this->body,
        ];
    }

    /**
     * See https://github.com/zendframework/zend-diactoros/blob/754a2ceb7ab753aafe6e3a70a1fb0370bde8995c/src/Response/SapiEmitterTrait.php#L96
     */
    private function capitalizeHeaderName(string $name): string
    {
        $name = str_replace('-', ' ', $name);
        $name = ucwords($name);
        return str_replace(' ', '-', $name);
    }
}
