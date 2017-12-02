<?php
declare(strict_types=1);

namespace PhpLambda\Bridge\Symfony;

use Symfony\Component\HttpFoundation\Request;

/**
 * Create a Symfony request from a lambda event.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class RequestFactory
{
    public function createRequest(array $event) : Request
    {
        $method = $event['httpMethod'] ?? 'GET';
        $query = $event['queryStringParameters'] ?? '';
        parse_str($event['body'] ?? '', $request);
        $attributes = [];
        // TODO no cookies support for now
        $cookies = [];
        // No file upload support for now
        $files = [];

        $server = [
            'SERVER_PROTOCOL' => $event['requestContext']['protocol'] ?? null,
            'REQUEST_METHOD' => $method,
            'REQUEST_TIME' => $event['requestContext']['requestTimeEpoch'] ?? time(),
            'QUERY_STRING' => Request::normalizeQueryString(http_build_query($query)),
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_URI' => $event['requestContext']['path'] ?? '/',
        ];
        // Inspired from \Symfony\Component\HttpFoundation\Request::overrideGlobals()
        foreach (($event['headers'] ?? []) as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $server[$key] = implode(', ', $value);
            } else {
                $server['HTTP_'.$key] = implode(', ', $value);
            }
        }

        return new Request(
            $query,
            $request,
            $attributes,
            $cookies,
            $files,
            $server
        );
    }
}
