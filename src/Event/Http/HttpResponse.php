<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Runtime\LambdaResult;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 */
final class HttpResponse
{
    private int $statusCode;
    private array $headers;
    /** @var string|iterable<string> */
    private string|iterable $body;

    /**
     * The body is a string, or its chunks when it is produced progressively (an iterable, e.g. a generator).
     * To write a body produced by `echo`, see HttpResponseWriter::capture().
     *
     * @param string|iterable<string> $body
     * @param array<string|string[]>  $headers
     */
    public function __construct(string|iterable $body, array $headers = [], int $statusCode = 200)
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    public function toApiGatewayFormat(bool $multiHeaders = false, ?string $awsRequestId = null): array
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

        $this->checkHeadersSize($headers, $awsRequestId);

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
            'body' => $base64Encoding ? base64_encode($this->bodyAsString()) : $this->bodyAsString(),
        ];
    }

    /**
     * See https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html#http-api-develop-integrations-lambda.response
     */
    public function toApiGatewayFormatV2(?string $awsRequestId = null): array
    {
        $base64Encoding = (bool) getenv('BREF_BINARY_RESPONSES');
        ['cookies' => $cookies, 'statusCode' => $statusCode, 'headers' => $headers] = $this->toApiGatewayFormatV2WithoutBody($awsRequestId);

        return [
            'cookies' => $cookies,
            'isBase64Encoded' => $base64Encoding,
            'statusCode' => $statusCode,
            'headers' => $headers,
            'body' => $base64Encoding ? base64_encode($this->bodyAsString()) : $this->bodyAsString(),
        ];
    }

    /**
     * The format of Lambda's streamed HTTP responses (Function URLs in `RESPONSE_STREAM` mode, REST APIs in
     * `STREAM` mode): the status code and headers as JSON, 8 null bytes, then the body as is (binary included,
     * no base64).
     *
     * A body produced by an iterable can only be sent once.
     */
    public function toStreamedResponse(?string $awsRequestId = null): LambdaResult
    {
        return new LambdaResult(
            HttpResponseWriter::STREAMED_CONTENT_TYPE,
            fn (callable $send, callable $end) => $this->sendTo(HttpResponseWriter::streamed($send, $end, $awsRequestId)),
        );
    }

    /**
     * Write the response, its body chunk by chunk. A body produced by an iterable can only be sent once.
     */
    public function sendTo(HttpResponseWriter $writer): void
    {
        $writer->start($this->statusCode, $this->headers);
        LambdaResult::writeChunks(is_string($this->body) ? [$this->body] : $this->body, $writer->write(...));
    }

    /**
     * The status code and headers of Lambda's streamed HTTP responses (see toStreamedResponse()).
     *
     * @internal
     */
    public function toStreamedPrelude(?string $awsRequestId = null): string
    {
        return json_encode($this->toApiGatewayFormatV2WithoutBody($awsRequestId), JSON_THROW_ON_ERROR) . "\0\0\0\0\0\0\0\0";
    }

    private function toApiGatewayFormatV2WithoutBody(?string $awsRequestId): array
    {
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

        $this->checkHeadersSize(array_merge(
            $headers,
            ['Set-Cookie' => $cookies], // include cookies in the size check
        ), $awsRequestId);

        // The headers must be a JSON object. If the PHP array is empty it is
        // serialized to `[]` (we want `{}`) so we force it to an empty object.
        $headers = empty($headers) ? new \stdClass : $headers;

        return [
            'cookies' => $cookies,
            'statusCode' => $this->statusCode,
            'headers' => $headers,
        ];
    }

    private function bodyAsString(): string
    {
        if (is_string($this->body)) {
            return $this->body;
        }
        $body = '';
        foreach ($this->body as $chunk) {
            $body .= $chunk;
        }

        return $this->body = $body;
    }

    /**
     * See https://github.com/zendframework/zend-diactoros/blob/754a2ceb7ab753aafe6e3a70a1fb0370bde8995c/src/Response/SapiEmitterTrait.php#L96
     */
    private function capitalizeHeaderName(string $name): string
    {
        $name = str_replace('-', ' ', strtolower($name));
        $name = ucwords($name);
        return str_replace(' ', '-', $name);
    }

    /**
     * API Gateway v1 and v2 have a headers total max size of 10 KB.
     * ALB has a max size of 32 KB.
     * It's hard to calculate the exact size of headers here, so we just
     * estimate it roughly: if above 9.5 KB we log a warning.
     *
     * @param array<string|string[]> $headers
     */
    private function checkHeadersSize(array $headers, ?string $awsRequestId): void
    {
        $estimatedHeadersSize = 0;
        foreach ($headers as $name => $values) {
            $estimatedHeadersSize += strlen($name);
            if (is_array($values)) {
                foreach ($values as $value) {
                    $estimatedHeadersSize += strlen($value);
                }
            } else {
                $estimatedHeadersSize += strlen($values);
            }
        }

        if ($estimatedHeadersSize > 9_500) {
            echo "$awsRequestId\tWARNING\tThe total size of HTTP response headers is estimated to be above 10 KB, which is the API Gateway limit. If the limit is reached, the HTTP response will be a 500 error.\n";
        }
    }
}
