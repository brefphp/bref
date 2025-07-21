<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Generator;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 */
final class StreamedHttpResponse
{
    private int $statusCode;
    private array $headers;
    private Generator $body;

    /**
     * @param array<string|string[]> $headers
     */
    public function __construct(Generator $body, array $headers = [], int $statusCode = 200)
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    public function toApiGatewayFormat(bool $multiHeaders = false): array|\Generator
    {
        $isStreamedMode = (bool) getenv('BREF_STREAMED_MODE');
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

        if ($isStreamedMode) {
            yield json_encode([
                'statusCode' => $this->statusCode,
                $headersKey => $headers,
            ]);

            yield "\0\0\0\0\0\0\0\0";

            foreach ($this->body as $dataChunk) {
                yield $dataChunk;
            }
        } else {
            $dataChunk = '';

            while ($this->body->valid()) {
                $dataChunk .= $this->body->current();

                $this->body->next();
            }

            return [
                'isBase64Encoded' => $base64Encoding,
                'statusCode' => $this->statusCode,
                $headersKey => $headers,
                'body' => $base64Encoding ? base64_encode($dataChunk) : $dataChunk,
            ];
        }
    }

    /**
     * See https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html#http-api-develop-integrations-lambda.response
     */
    public function toApiGatewayFormatV2(): array|\Generator
    {
        $isStreamedMode = (bool) getenv('BREF_STREAMED_MODE');
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
                $headers[$name] = is_array($values) ? implode(', ', $values) : $values;
            }
        }

        // The headers must be a JSON object. If the PHP array is empty it is
        // serialized to `[]` (we want `{}`) so we force it to an empty object.
        $headers = empty($headers) ? new \stdClass : $headers;

        if ($isStreamedMode) {
            yield json_encode([
                'cookies' => $cookies,
                'statusCode' => $this->statusCode,
                'headers' => $headers,
            ]);

            yield "\0\0\0\0\0\0\0\0";

            foreach ($this->body as $dataChunk) {
                yield $dataChunk;
            }
        } else {
            $dataChunk = '';

            while ($this->body->valid()) {
                $dataChunk .= $this->body->current();

                $this->body->next();
            }

            return [
                'cookies' => $cookies,
                'isBase64Encoded' => $base64Encoding,
                'statusCode' => $this->statusCode,
                'headers' => $headers,
                'body' => $base64Encoding ? base64_encode($dataChunk) : $dataChunk,
            ];
        }
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
