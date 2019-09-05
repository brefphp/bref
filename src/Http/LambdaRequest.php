<?php

declare(strict_types=1);

namespace Bref\Http;

use Symfony\Component\HttpFoundation\Request;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LambdaRequest
{
    private $event;

    public static function create(array $event)
    {
        $model = new self();
        $model->event = $event;

        return $model;
    }

    public function getRawEvent()
    {
        return $this->event;
    }

    public function getSymfonyRequest()
    {
        $event = $this->event;
        $requestBody = $event['body'] ?? '';
        if ($event['isBase64Encoded'] ?? false) {
            $requestBody = base64_decode($requestBody);
        }

        $queryString = $this->getQueryString($event);
        $uri = $event['path'] ?? '/';
        if (! empty($queryString)) {
            $uri .= '?' . $queryString;
        }

        $method = strtoupper($event['httpMethod']);
        $request = Request::create($uri, $method, [], [], [], [], $requestBody);

        // Normalize headers
        if (isset($event['multiValueHeaders'])) {
            $headers = $event['multiValueHeaders'];
        } else {
            $headers = $event['headers'] ?? [];
            // Turn the headers array into a multi-value array to simplify the code below
            $headers = array_map(function ($value): array {
                return [$value];
            }, $headers);
        }
        $headers = array_change_key_case($headers, CASE_LOWER);

        $request->headers->add($headers);

        return $request;
    }

    public function getPsr7Request()
    {
        // TODO write me
    }

    private function getQueryString(array $event): string
    {
        if (isset($event['multiValueQueryStringParameters']) && $event['multiValueQueryStringParameters']) {
            $queryParameters = [];
            /*
             * Watch out: to support multiple query string parameters with the same name like:
             *     ?array[]=val1&array[]=val2
             * we need to support "multi-value query string", else only the 'val2' value will survive.
             * At the moment we only take the first value (which means we DON'T support multiple values),
             * this needs to be implemented below in the future.
             */
            foreach ($event['multiValueQueryStringParameters'] as $key => $value) {
                $queryParameters[$key] = $value[0];
            }
            return http_build_query($queryParameters);
        }

        if (empty($event['queryStringParameters'])) {
            return '';
        }

        /*
         * Watch out in the future if using $event['queryStringParameters'] directly!
         *
         * (that is no longer the case here but it was in the past with the PSR-7 bridge, and it might be
         * reintroduced in the future)
         *
         * queryStringParameters does not handle correctly arrays in parameters
         * ?array[key]=value gives ['array[key]' => 'value'] while we want ['array' => ['key' = > 'value']]
         * In that case we should recreate the original query string and use parse_str which handles correctly arrays
         */
        return http_build_query($event['queryStringParameters']);
    }
}
