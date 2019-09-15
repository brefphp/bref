<?php declare(strict_types=1);

namespace Bref\Http;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Formats the response expected by AWS Lambda and the API Gateway integration.
 *
 * @internal
 */
final class LambdaResponse
{
    /** @var int */
    private $statusCode = 200;

    /** @var array<string, string> */
    private $headers;

    /** @var string */
    private $body;

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
            $headers[strtolower($name)] = implode($values, '; ');
        }

        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        return new self($response->getStatusCode(), $headers, $body);
    }

    public static function fromSymfonyResponse(Response $response): self
    {
        $headers = [];
        foreach ($response->headers->all() as $name => $values) {
            $headers[strtolower($name)] = implode($values, '; ');
        }

        $body = $response->getContent();

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

    public function toApiGatewayFormat(bool $multiHeaders = false): array
    {
        // The headers must be a JSON object. If the PHP array is empty it is
        // serialized to `[]` (we want `{}`) so we force it to an empty object.
        $headers = empty($this->headers) ? new \stdClass : $this->headers;

        // Support for multi-value headers
        $headersKey = $multiHeaders ? 'multiValueHeaders' : 'headers';

        // This is the format required by the AWS_PROXY lambda integration
        // See https://stackoverflow.com/questions/43708017/aws-lambda-api-gateway-error-malformed-lambda-proxy-response
        return [
            'isBase64Encoded' => false,
            'statusCode' => $this->statusCode,
            $headersKey => $headers,
            'body' => $this->body,
        ];
    }
}
