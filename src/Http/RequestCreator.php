<?php declare(strict_types=1);

namespace Bref\Http;

use Bref\Context\Context;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Nyholm\Psr7Server\ServerRequestCreatorInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Riverline\MultiPartParser\StreamedPart;

/**
 * Creates a PSR-7 request from an incoming API Gateway request
 */
final class RequestCreator
{
    /** @var ServerRequestCreatorInterface */
    private $serverRequestCreator;

    /** @var UploadedFileFactoryInterface */
    private $uploadedFileFactory;

    /** @var StreamFactoryInterface */
    private $streamFactory;

    public function __construct(
        ?ServerRequestCreatorInterface $serverRequestCreator = null,
        ?UploadedFileFactoryInterface $uploadedFileFactory = null,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        if (($serverRequestCreator === null)
            || ($uploadedFileFactory === null)
            || ($streamFactory === null)
        ) {
            $factory = new Psr17Factory;
            if ($uploadedFileFactory === null) {
                $uploadedFileFactory = $factory;
            }
            if ($streamFactory === null) {
                $streamFactory = $factory;
            }
            if ($serverRequestCreator === null) {
                $serverRequestCreator = new ServerRequestCreator($factory, $factory, $factory, $factory);
            }
        }
        $this->serverRequestCreator = $serverRequestCreator;
        $this->uploadedFileFactory = $uploadedFileFactory;
        $this->streamFactory = $streamFactory;
    }

    private function getRequestMethod(array $event): string
    {
        return $event['httpMethod'] ?? 'GET';
    }

    /**
     * @param array $event
     */
    public function createRequest(array $event, Context $context): ServerRequestInterface
    {
        $content = $this->getDecodedContent($event);
        $headers = $this->getHeaders($event, $content);
        $stream = $this->streamFactory->createStream($content);
        $stream->rewind();

        $request = $this->serverRequestCreator->fromArrays(
            $this->getServerParams($event, $context, $headers, $content),
            $headers,
            $this->getCookies($headers),
            $this->getQueryParams($event),
            [],
            [],
            $stream
        );

        $request = $request->withParsedBody($this->getParsedBody($headers, $content));
        $request = $request->withUploadedFiles($this->getUploadedFiles($headers, $content));

        $request = $this->setAttributes($request, $event, $context);

        $request = $request->withBody($stream);

        return $request;
    }

    /**
     * @param array $event
     * @return array
     */
    private function getHeaders(array $event, string $content): array
    {
        // Normalize headers
        if (isset($event['multiValueHeaders'])) {
            $headers = $event['multiValueHeaders'];
        } else {
            $headers = $event['headers'] ?? [];
            // Turn the headers array into a multi-value array
            $headers = array_map(function ($value): array {
                return [$value];
            }, $headers);
        }

        $keys = array_map(function (string $key): string {
            return strtolower($key);
        }, array_keys($headers));

        $headers = array_combine($keys, array_values($headers));

        // See https://stackoverflow.com/a/5519834/245552
        if (! empty($content) && $event['httpMethod'] !== 'TRACE') {
            if (! isset($headers['content-type'])) {
                $headers['content-type'] = ['application/x-www-form-urlencoded'];
            }

            if (! isset($headers['content-length'])) {
                $headers['content-length'] = [strlen($this->getDecodedContent($event))];
            }
        }

        return $headers;
    }

    private function getQueryString(array $event): string
    {
        $queryString = [];

        if (! array_key_exists('multiValueQueryStringParameters', $event)) {
            foreach ($event['queryStringParameters'] ?? [] as $key => $value) {
                $queryString[] = sprintf('%s=%s', $key, urlencode($value));
            }

            return implode('&', $queryString);
        }

        $multiValue = $event['multiValueQueryStringParameters'] ?? [];

        foreach ($multiValue as $key => $values) {
            foreach ($values as $value) {
                $queryString[] = sprintf('%s=%s', $key, urlencode($value));
            }
        }

        return implode('&', $queryString);
    }

    /**
     * Get the HTTP protocol and version
     */
    private function getProtocol(array $event): string
    {
        if (! array_key_exists('requestContext', $event)
            || ! array_key_exists('protocol', $event['requestContext'])
        ) {
            return 'HTTP/1.1';
        }

        return $event['requestContext']['protocol'];
    }

    /**
     * @param array $event
     * @param mixed $context
     */
    private function setAttributes(ServerRequestInterface $request, array $event, $context): ServerRequestInterface
    {
        $request = $request->withAttribute('event', $event);
        $request = $request->withAttribute('context', $context);

        return $request;
    }

    /**
     * Get the server parameters
     *
     * @param array $event
     * @param mixed $context
     * @param array $headers
     * @return array
     */
    private function getServerParams(array $event, $context, array $headers, string $content): array
    {
        $params = [];

        if (array_key_exists('x-forwarded-proto', $headers) && count($headers['x-forwarded-proto'])) {
            $params['HTTP_X_FORWARDED_PROTO'] = $headers['x-forwarded-proto'][0];
        }

        if (array_key_exists('x-forwarded-port', $headers) && count($headers['x-forwarded-port'])) {
            $params['HTTP_X_FORWARDED_PORT'] = $headers['x-forwarded-port'][0];
        }

        if (array_key_exists('host', $headers) && count($headers['host'])) {
            $params['HTTP_HOST'] = $headers['host'][0];
        }

        $params['REQUEST_METHOD'] = $this->getRequestMethod($event);
        $params['REQUEST_URI'] = $event['path'] ?? $event['requestContext']['path'] ?? '/';
        $params['QUERY_STRING'] = $this->getQueryString($event);
        $params['SERVER_PROTOCOL'] = $this->getProtocol($event);
        $params['REQUEST_TIME'] = $event['requestContext']['requestTimeEpoch'] ?? time();

        $params = array_merge($_SERVER, $params);
        return $params;
    }

    /**
     * Get the parsed body (eg. structured data, form data)
     *
     * @param array $headers
     * @return array|null
     */
    private function getParsedBody(array $headers, string $content): ?array
    {
        if (empty($content)) {
            return [];
        }
        $contentType = $this->getContentType($headers);

        if (strpos(mb_strtolower($contentType), 'multipart/form-data') !== false) {
            return $this->getParsedMultipartFormData($headers, $content, false);
        }

        if (strpos(mb_strtolower($contentType), 'application/x-www-form-urlencoded') !== false) {
            return $this->getParsedUrlEncodedForm($content);
        }

        return null;
    }

    /**
     * Get the cookies from headers
     *
     * @param array $headers
     * @return array
     */
    private function getCookies(array $headers): array
    {
        $cookies = [];
        if (array_key_exists('cookie', $headers) && is_array($headers['cookie'])) {
            foreach ($headers['cookie'] as $cookieHeader) {
                // https://stackoverflow.com/a/54623825
                // Seems to want to encode + to urlencoded "+", not space, @todo
                unset($results);
                parse_str(strtr($cookieHeader, ['&' => '%26', '+' => '%20', ';' => '&']), $results);
                $cookies = array_merge($cookies, $results);
            }
        }
        return $cookies;
    }

    /**
     * Returns the request body
     */
    private function getDecodedContent(array $event): string
    {
        if (! array_key_exists('body', $event)) {
            return ''; // @todo
        }

        $body = $event['body'] ?? '';
        $isBase64Encoded = $event['isBase64Encoded'] ?? false;

        if ($isBase64Encoded) {
            $body = base64_decode($body);
        }

        return $body;
    }

    /**
     * Reconstruct the query string and use native php parse_str to read it
     *
     * @param array $event
     * @return mixed
     */
    private function getQueryParams(array $event)
    {
        parse_str($this->getQueryString($event), $results);
        return $results;
    }

    /**
     * @param array $headers
     */
    private function getContentType(array $headers): ?string
    {
        return $headers['content-type'][0] ?? null;
    }

    /**
     * Return the content of an application/x-www-form-urlencoded request
     *
     * @return mixed
     */
    private function getParsedUrlEncodedForm(string $content)
    {
        parse_str($content, $parsedBody);
        return $parsedBody;
    }

    /**
     * Parse a multipart/form-data request
     *
     * @param array $headers
     * @return array
     */
    private function getParsedMultipartFormData(array $headers, string $content, bool $files): array
    {
        $contentStream = fopen('php://temp', 'rw+');
        $contentTypeHeaders = $headers['content-type'];
        foreach ($contentTypeHeaders as $headerLine) {
            fwrite($contentStream, sprintf('Content-Type: %s%s', $headerLine, PHP_EOL));
        }
        fwrite($contentStream, PHP_EOL);
        fwrite($contentStream, $content);
        rewind($contentStream);
        $document = new StreamedPart($contentStream);

        $body = [];

        foreach ($document->getParts() as $part) {
            $name = $part->getName();
            if ($name !== null) {
                if ($part->isFile() && $files) {
                    $body = $this->withExtractedMultipartData($body, $name, $this->getFileFromPart($part));
                } elseif (! $part->isFile() && ! $files) {
                    $body = $this->withExtractedMultipartData($body, $name, $part->getBody());
                }
            }
        }

        return $body;
    }

    /**
     * Recursively converts arrays in keys into actual nested arrays
     *
     * Eg. "some[nested][key][]" => "24"
     * becomes
     * "some" => [
     *     "nested" => [
     *         "key" => [
     *             "24"
     *         ]
     *     ]
     * ]
     *
     * @param array                                       $data
     * @param int|float|string|UploadedFileInterface|null $value
     * @return array<int|string, \Psr\Http\Message\UploadedFileInterface|string>
     */
    private function withExtractedMultipartData(array $data, string $key, $value): array
    {
        // Match against 'key[key]....' = something
        if (preg_match('/(?<full>(?<base>[^\[\]]+)(?<wrapper>\[(?<key>[^\[\]]+)\]))(?<tail>.*)/', $key, $matches)) {
            $subKey = $matches['key'] . $matches['tail'];
            $data[$matches['base']] = $this->withExtractedMultipartData(
                $data[$matches['base']] ?? [],
                $subKey,
                $value
            );

            return $data;
        }

        // match against 'key[]' = something
        if (preg_match('/(?<name>[^\[\]]+)\[\s*\]/', $key, $matches)) {
            $data[$matches['name']][] = $value;

            return $data;
        }

        if (is_scalar($key)) {
            $data[$key] = $value;

            return $data;
        }

        return $data;
    }

    private function getFileFromPart(StreamedPart $part): UploadedFileInterface
    {
        $stream = $this->streamFactory->createStream($part->getBody());
        $stream->rewind();
        return $this->uploadedFileFactory->createUploadedFile(
            $stream,
            null,
            \UPLOAD_ERR_OK,
            $part->getFileName(),
            $part->getMimeType()
        );
    }

    /**
     * @param array $headers
     * @return array Tree of UploadedFileInterface
     */
    private function getUploadedFiles(array $headers, string $content): array
    {
        $contentType = $this->getContentType($headers);

        if ($contentType === null || strpos(mb_strtolower($contentType), 'multipart/form-data') === false) {
            return [];
        }

        return $this->getParsedMultipartFormData($headers, $content, true);
    }
}
