<?php
declare(strict_types=1);

namespace Bref\Bridge\Psr7;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;

/**
 * Creates PSR-7 requests.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class RequestFactory
{
    /**
     * Create a PSR-7 server request from an AWS Lambda HTTP event.
     */
    public static function fromLambdaEvent(array $event) : ServerRequestInterface
    {
        $method = $event['httpMethod'] ?? 'GET';
        $query = $event['queryStringParameters'] ?? [];
        $bodyString = $event['body'] ?? '';
        $body = self::createBodyStream($bodyString);
        $parsedBody = null;
        $files = [];
        $uri = $event['requestContext']['path'] ?? '/';
        $headers = $event['headers'] ?? [];
        $protocolVersion = $event['requestContext']['protocol'] ?? '1.1';
        // TODO Parse HTTP headers for cookies.
        $cookies = [];

        $contentType = $headers['Content-Type'] ?? null;
        /*
         * TODO Multipart form uploads are not supported yet.
         */
        if ($method === 'POST' && $contentType === 'application/x-www-form-urlencoded') {
            parse_str($bodyString, $parsedBody);
        }

        $server = [
            'SERVER_PROTOCOL' => $protocolVersion,
            'REQUEST_METHOD' => $method,
            'REQUEST_TIME' => $event['requestContext']['requestTimeEpoch'] ?? time(),
            'QUERY_STRING' => $query ? http_build_query($query) : '',
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_URI' => $uri,
        ];

        return new ServerRequest(
            $server,
            $files,
            $uri,
            $method,
            $body,
            $headers,
            $cookies,
            $query,
            $parsedBody,
            $protocolVersion
        );
    }

    private static function createBodyStream(string $body) : StreamInterface
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $body);
        rewind($stream);

        return new Stream($stream);
    }
}
