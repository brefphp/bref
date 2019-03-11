<?php declare(strict_types=1);

namespace Bref\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 */
class LambdaResponse
{
    /** @var int */
    private $statusCode = 200;

    /** @var array */
    private $headers;

    /** @var string */
    private $body;

    /** @var array */
    private $http_codes = [100 => 'Continue', 101 => 'Switching Protocols', 102 => 'Processing', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 207 => 'Multi-Status', 208 => 'Already Reported', 226 => 'IM Used', 300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Found', 303 => 'See Other', 304 => 'Not Modified', 305 => 'Use Proxy', 306 => 'Switch Proxy', 307 => 'Temporary Redirect', 308 => 'Permanent Redirect', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 407 => 'Proxy Authentication Required', 408 => 'Request Timeout', 409 => 'Conflict', 410 => 'Gone', 411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Long', 415 => 'Unsupported Media Type', 416 => 'Requested Range Not Satisfiable', 417 => 'Expectation Failed', 418 => 'I\'m a teapot', 419 => 'Authentication Timeout', 420 => 'Enhance Your Calm', 420 => 'Method Failure', 422 => 'Unprocessable Entity', 423 => 'Locked', 424 => 'Failed Dependency', 424 => 'Method Failure', 425 => 'Unordered Collection', 426 => 'Upgrade Required', 428 => 'Precondition Required', 429 => 'Too Many Requests', 431 => 'Request Header Fields Too Large', 444 => 'No Response', 449 => 'Retry With', 450 => 'Blocked by Windows Parental Controls', 451 => 'Redirect', 451 => 'Unavailable For Legal Reasons', 494 => 'Request Header Too Large', 495 => 'Cert Error', 496 => 'No Cert', 497 => 'HTTP to HTTPS', 499 => 'Client Closed Request', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 503 => 'Service Unavailable', 504 => 'Gateway Timeout', 505 => 'HTTP Version Not Supported', 506 => 'Variant Also Negotiates', 507 => 'Insufficient Storage', 508 => 'Loop Detected', 509 => 'Bandwidth Limit Exceeded', 510 => 'Not Extended', 511 => 'Network Authentication Required', 598 => 'Network read timeout error', 599 => 'Network connect timeout error'];
    public function __construct(int $statusCode, array $headers, string $body)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->body = $body;
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

        return new self($response->getStatusCode(), $headers, $body);
    }

    public static function fromHtml(string $html): self
    {
        return new self(
            200,
            [
                'Content-Type' => 'text/html; charset=utf-8',
            ],
            $html
        );
    }

    public function toApiGatewayFormat(): array
    {
        // The headers must be a JSON object. If the PHP array is empty it is
        // serialized to `[]` (we want `{}`) so we force it to an empty object.
        $headers = empty($this->headers) ? new \stdClass : $this->headers;
        // This is the format required by the AWS_PROXY lambda integration
        // See https://stackoverflow.com/questions/43708017/aws-lambda-api-gateway-error-malformed-lambda-proxy-response
        return [
            'isBase64Encoded' => false,
            'statusCode' => $this->statusCode,
            'headers' => $headers,
            'body' => $this->body,
        ];
    }
    public function toALBFormat(): array
    {
        // This is the format required by the ALB lambda integration https://aws.amazon.com/pt/blogs/networking-and-content-delivery/lambda-functions-as-targets-for-application-load-balancers/
        $statusDescription = "$this->statusCode ";
        $statusDescription .= $this->http_codes[$this->statusCode] ?? 'Unknown';
        $headers = empty($this->headers) ? new \stdClass : $this->headers;
        return [
            'isBase64Encoded' => false,
            'statusCode' => $this->statusCode,
            'statusDescription' => $statusDescription,
            'multiValueHeaders' => $headers,
            'body' => $this->body,
            'isALB' => true,
        ];
    }
}
