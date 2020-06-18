<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Event\InvalidLambdaEvent;
use Bref\Event\LambdaEvent;

/**
 * Represents a Lambda event that comes from a HTTP request.
 *
 * The event can come from API Gateway or ALB (Application Load Balancer).
 *
 * See the following for details on the JSON payloads for HTTP APIs;
 * https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html#http-api-develop-integrations-lambda.proxy-format
 */
final class HttpRequestEvent implements LambdaEvent
{
    /** @var array */
    private $event;
    /** @var string */
    private $method;
    /** @var array */
    private $headers;
    /** @var string */
    private $queryString;
    /** @var float */
    private $payloadVersion;

    public function __construct(array $event)
    {
        // version 1.0 of the HTTP payload
        if (isset($event['httpMethod'])) {
            $this->method = strtoupper($event['httpMethod']);
        } elseif (isset($event['requestContext']['http']['method'])) {
            // version 2.0 - https://docs.aws.amazon.com/apigateway/latest/developerguide/http-api-develop-integrations-lambda.html#http-api-develop-integrations-lambda.proxy-format
            $this->method = strtoupper($event['requestContext']['http']['method']);
        } else {
            throw new InvalidLambdaEvent('API Gateway or ALB', $event);
        }

        $this->payloadVersion = (float) ($event['version'] ?? '1.0');
        $this->event = $event;
        $this->queryString = $this->rebuildQueryString();
        $this->headers = $this->extractHeaders();
    }

    public function toArray(): array
    {
        return $this->event;
    }

    public function getBody(): string
    {
        $requestBody = $this->event['body'] ?? '';
        if ($this->event['isBase64Encoded'] ?? false) {
            $requestBody = base64_decode($requestBody);
        }
        return $requestBody;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasMultiHeader(): bool
    {
        if ($this->payloadVersion === 2.0) {
            return false;
        }

        return isset($this->event['multiValueHeaders']);
    }

    public function getProtocol(): string
    {
        return $this->event['requestContext']['protocol'] ?? 'HTTP/1.1';
    }

    public function getProtocolVersion(): string
    {
        return ltrim($this->getProtocol(), 'HTTP/');
    }

    public function getContentType(): ?string
    {
        return $this->headers['content-type'][0] ?? null;
    }

    public function getRemotePort(): int
    {
        return (int) ($this->headers['x-forwarded-port'][0] ?? 80);
    }

    public function getServerPort(): int
    {
        return (int) ($this->headers['x-forwarded-port'][0] ?? 80);
    }

    public function getServerName(): string
    {
        return $this->headers['host'][0] ?? 'localhost';
    }

    public function getPath(): string
    {
        if ($this->payloadVersion >= 2) {
            return $this->event['rawPath'] ?? '/';
        }

        return $this->event['path'] ?? '/';
    }

    public function getUri(): string
    {
        $queryString = $this->queryString;
        $uri = $this->getPath();
        if (! empty($queryString)) {
            $uri .= '?' . $queryString;
        }
        return $uri;
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function getQueryParameters(): array
    {
        parse_str($this->queryString, $query);
        return $query;
    }

    public function getRequestContext(): array
    {
        return $this->event['requestContext'] ?? [];
    }

    public function getCookies(): array
    {
        if ($this->payloadVersion === 2.0) {
            if (! isset($this->event['cookies'])) {
                return [];
            }
            $cookieParts = $this->event['cookies'];
        } else {
            if (! isset($this->headers['cookie'])) {
                return [];
            }
            // Multiple "Cookie" headers are not authorized
            // https://stackoverflow.com/questions/16305814/are-multiple-cookie-headers-allowed-in-an-http-request
            $cookieHeader = $this->headers['cookie'][0];
            $cookieParts = explode('; ', $cookieHeader);
        }

        $cookies = [];
        foreach ($cookieParts as $cookiePart) {
            [$cookieName, $cookieValue] = explode('=', $cookiePart, 2);
            $cookies[$cookieName] = urldecode($cookieValue);
        }
        return $cookies;
    }

    private function rebuildQueryString(): string
    {
        if ($this->payloadVersion === 2.0) {
            $queryString = $this->event['rawQueryString'] ?? '';
            // We re-parse the query string to make sur it is URL-encoded
            // Why? To match the format we get when using PHP outside of Lambda (we get the query string URL-encoded)
            parse_str($queryString, $queryParameters);
            return http_build_query($queryParameters);
        }

        if (isset($this->event['multiValueQueryStringParameters']) && $this->event['multiValueQueryStringParameters']) {
            $queryParameters = [];
            /*
             * Watch out: to support multiple query string parameters with the same name like:
             *     ?array[]=val1&array[]=val2
             * we need to support "multi-value query string", else only the 'val2' value will survive.
             * At the moment we only take the first value (which means we DON'T support multiple values),
             * this needs to be implemented below in the future.
             */
            foreach ($this->event['multiValueQueryStringParameters'] as $key => $value) {
                $queryParameters[$key] = $value[0];
            }
            return http_build_query($queryParameters);
        }

        if (empty($this->event['queryStringParameters'])) {
            return '';
        }

        /*
         * Watch out: do not use $event['queryStringParameters'] directly!
         *
         * (that is no longer the case here but it was in the past with Bref 0.2)
         *
         * queryStringParameters does not handle correctly arrays in parameters
         * ?array[key]=value gives ['array[key]' => 'value'] while we want ['array' => ['key' = > 'value']]
         * In that case we should recreate the original query string and use parse_str which handles correctly arrays
         */
        return http_build_query($this->event['queryStringParameters']);
    }

    private function extractHeaders(): array
    {
        // Normalize headers
        if (isset($this->event['multiValueHeaders'])) {
            $headers = $this->event['multiValueHeaders'];
        } else {
            $headers = $this->event['headers'] ?? [];
            // Turn the headers array into a multi-value array to simplify the code below
            $headers = array_map(function ($value): array {
                return [$value];
            }, $headers);
        }
        $headers = array_change_key_case($headers, CASE_LOWER);

        $hasBody = ! empty($this->event['body']);
        // See https://stackoverflow.com/a/5519834/245552
        if ($hasBody && ! isset($headers['content-type'])) {
            $headers['content-type'] = ['application/x-www-form-urlencoded'];
        }

        // Auto-add the Content-Length header if it wasn't provided
        // See https://github.com/brefphp/bref/issues/162
        if ($hasBody && ! isset($headers['content-length'])) {
            $headers['content-length'] = [strlen($this->getBody())];
        }

        // Cookies are separated from headers in payload v2, we re-add them in there
        // so that we have the full original HTTP request
        if ($this->payloadVersion === 2.0 && ! empty($this->event['cookies'])) {
            $cookieHeader = implode('; ', $this->event['cookies']);
            $headers['cookie'] = [$cookieHeader];
        }

        return $headers;
    }
}
