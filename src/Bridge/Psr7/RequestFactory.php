<?php
declare(strict_types=1);

namespace PhpLambda\Bridge\Psr7;

use Slim\Http\Environment;
use Slim\Http\Request;

/**
 * Create a PSR-7 request from a lambda event.
 *
 * TODO use Diactoros to have less dependencies.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class RequestFactory
{
    public function fromLambdaEvent(array $event) : Request
    {
        $method = $event['httpMethod'] ?? 'GET';
        $query = $event['queryStringParameters'] ?? '';
        parse_str($event['body'] ?? '', $request);

        $server = [
            'SERVER_PROTOCOL' => $event['requestContext']['protocol'] ?? null,
            'REQUEST_METHOD' => $method,
            'REQUEST_TIME' => $event['requestContext']['requestTimeEpoch'] ?? time(),
            'QUERY_STRING' => $query ? http_build_query($query) : '',
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_URI' => $event['requestContext']['path'] ?? '/',
        ];

        // Inspired from \Symfony\Component\HttpFoundation\Request::overrideGlobals()
        foreach (($event['headers'] ?? []) as $key => $value) {
            $key = strtoupper(str_replace('-', '_', $key));
            if (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $server[$key] = implode(', ', $value);
            } else {
                $server['HTTP_'.$key] = implode(', ', (array) $value);
            }
        }

        $request = Request::createFromEnvironment(new Environment($server));
        $request = $request->withParsedBody($request);

        return $request;
    }
}
