<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Runtime\StreamedResponse;
use Generator;

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
     * @param string|iterable<string> $body    The body, or its chunks when it is produced progressively.
     * @param array<string|string[]>  $headers
     */
    public function __construct(string|iterable $body, array $headers = [], int $statusCode = 200)
    {
        $this->body = $body;
        $this->headers = $headers;
        $this->statusCode = $statusCode;
    }

    /**
     * @param bool $acceptsGzip Whether the client accepts gzip: compressible bodies are then compressed
     *                          (and base64-encoded), which also keeps more responses under Lambda's 6 MB limit.
     */
    public function toApiGatewayFormat(bool $multiHeaders = false, ?string $awsRequestId = null, bool $acceptsGzip = false): array
    {
        $gzip = $this->useGzip($acceptsGzip);
        $base64Encoding = $gzip || getenv('BREF_BINARY_RESPONSES');

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
            'body' => $this->encodedBody($gzip, $base64Encoding),
        ];
    }

    /**
     * See https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html#http-api-develop-integrations-lambda.response
     */
    public function toApiGatewayFormatV2(?string $awsRequestId = null, bool $acceptsGzip = false): array
    {
        $gzip = $this->useGzip($acceptsGzip);
        $base64Encoding = $gzip || getenv('BREF_BINARY_RESPONSES');

        ['cookies' => $cookies, 'statusCode' => $statusCode, 'headers' => $headers] = $this->toApiGatewayFormatV2WithoutBody($awsRequestId);

        return [
            'cookies' => $cookies,
            'isBase64Encoded' => $base64Encoding,
            'statusCode' => $statusCode,
            'headers' => $headers,
            'body' => $this->encodedBody($gzip, $base64Encoding),
        ];
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

    /**
     * The format of Lambda's streamed HTTP responses (Function URLs in `RESPONSE_STREAM` mode, API Gateway REST APIs
     * in `STREAM` mode): the status code and headers as JSON, 8 null bytes, then the body as is (binary included).
     *
     * @param bool $acceptsGzip Whether the client accepts gzip: compressible bodies are then compressed here,
     *                          because CloudFront does not compress streamed responses.
     */
    public function toStreamedResponse(?string $awsRequestId = null, bool $acceptsGzip = false): StreamedResponse
    {
        $gzip = $this->useGzip($acceptsGzip);
        $prelude = $this->toApiGatewayFormatV2WithoutBody($awsRequestId);

        return new StreamedResponse(
            'application/vnd.awslambda.http-integration-response',
            (function () use ($prelude, $gzip): Generator {
                yield json_encode($prelude, JSON_THROW_ON_ERROR) . "\0\0\0\0\0\0\0\0";
                $body = is_string($this->body) ? [$this->body] : $this->body;
                if (! $gzip) {
                    yield from $body;
                    return;
                }
                // Each chunk is flushed so that the client can decompress it right away (streaming keeps working)
                $deflate = deflate_init(ZLIB_ENCODING_GZIP, ['level' => self::gzipLevel()]);
                foreach ($body as $chunk) {
                    if ($chunk !== '') {
                        yield deflate_add($deflate, $chunk, ZLIB_SYNC_FLUSH);
                    }
                }
                yield deflate_add($deflate, '', ZLIB_FINISH);
            })(),
        );
    }

    /**
     * Decide whether to gzip the body, and set the headers accordingly.
     */
    private function useGzip(bool $acceptsGzip): bool
    {
        if (! $acceptsGzip || ! $this->isCompressible()) {
            return false;
        }
        $vary = $this->header('vary');
        $this->headers = array_filter(
            $this->headers,
            fn ($name) => ! in_array(strtolower((string) $name), ['content-length', 'vary'], true),
            ARRAY_FILTER_USE_KEY,
        );
        $this->headers['content-encoding'] = 'gzip';
        $this->headers['vary'] = trim(($vary ?? '') . ', Accept-Encoding', ', ');

        return true;
    }

    private function encodedBody(bool $gzip, bool $base64): string
    {
        $body = $this->bodyAsString();
        if ($gzip) {
            $body = gzencode($body, self::gzipLevel());
        }

        return $base64 ? base64_encode($body) : $body;
    }

    /**
     * Research only: the level is configurable to measure it.
     */
    private static function gzipLevel(): int
    {
        return (int) (getenv('BREF_GZIP_LEVEL') ?: 1);
    }

    private function isCompressible(): bool
    {
        if ($this->header('content-encoding') !== null || in_array($this->statusCode, [204, 206, 304], true)) {
            return false;
        }

        return (bool) preg_match(
            '#^(text/|application/(json|javascript|xml|[\w.-]+\+(json|xml))|image/svg\+xml)#i',
            $this->header('content-type') ?? '',
        );
    }

    private function header(string $name): ?string
    {
        foreach ($this->headers as $headerName => $values) {
            if (strtolower((string) $headerName) === $name) {
                return is_array($values) ? implode(', ', $values) : $values;
            }
        }

        return null;
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
