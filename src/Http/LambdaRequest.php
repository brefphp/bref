<?php

declare(strict_types=1);

namespace Bref\Http;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Riverline\MultiPartParser\Part;
use Symfony\Component\HttpFoundation\Request;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class LambdaRequest
{
    private $event;

    private function __construct()
    {
    }


    public static function create(array $event): self
    {
        $model = new self();
        $model->event = $event;

        return $model;
    }

    public function getRawEvent(): array
    {
        return $this->event;
    }

    public function getSymfonyRequest(): Request
    {
        if (!class_exists(Request::class)) {
            throw new \RuntimeException('You need to Symfony HTTP foundation to use this function. Please run "composer require symfony/http-foundation".');
        }

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

    public function getPsr7Request(): RequestInterface
    {
        if (!class_exists(Request::class)) {
            throw new \RuntimeException('You need to Symfony HTTP foundation to use this function. Please run "composer require guzzle/psr7".');
        }

        $method = $event['httpMethod'] ?? 'GET';
        $query = [];
        $bodyString = $event['body'] ?? '';
        $parsedBody = null;
        $files = [];
        $uri = $event['requestContext']['path'] ?? '/';
        $headers = $event['headers'] ?? [];
        $protocolVersion = $event['requestContext']['protocol'] ?? '1.1';
        if ($event['isBase64Encoded'] ?? false) {
            $bodyString = base64_decode($bodyString);
        }
        $body = self::createBodyStream($bodyString);
        /*
         * queryStringParameters does not handle correctly arrays in parameters
         * ?array[key]=value gives ['array[key]' => 'value'] while we want ['array' => ['key' => 'value']]
         * We recreate the original query string and we use parse_str which handles correctly arrays
         *
         * There's still an issue: AWS API Gateway does not support multiple query string parameters with the same name
         * So you can't use something like ?array[]=val1&array[]=val2 because only the 'val2' value will survive
         */
        $queryString = http_build_query($event['queryStringParameters'] ?? []);
        parse_str($queryString, $query);
        $cookies = [];
        if (isset($headers['Cookie'])) {
            $cookieParts = explode('; ', $headers['Cookie']);
            foreach ($cookieParts as $cookiePart) {
                [$cookieName, $cookieValue] = explode('=', $cookiePart, 2);
                $cookies[$cookieName] = urldecode($cookieValue);
            }
        }
        $contentType = $headers['content-type'] ?? $headers['Content-Type'] ?? null;
        if ($method === 'POST' && $contentType !== null) {
            /** @var string $contentType */
            if ($contentType === 'application/x-www-form-urlencoded') {
                parse_str($bodyString, $parsedBody);
            } else {
                $document = new Part("Content-type: $contentType\r\n\r\n" . $bodyString);
                if ($document->isMultiPart()) {
                    $parsedBody = [];
                    foreach ($document->getParts() as $part) {
                        if ($part->isFile()) {
                            $tmpPath = tempnam(sys_get_temp_dir(), 'bref_upload_');
                            if ($tmpPath === false) {
                                throw new \RuntimeException('Unable to create a temporary directory');
                            }
                            file_put_contents($tmpPath, $part->getBody());
                            $file = new UploadedFile($tmpPath, filesize($tmpPath), UPLOAD_ERR_OK, $part->getFileName(), $part->getMimeType());
                            self::parseKeyAndInsertValueInArray($files, $part->getName(), $file);
                        } else {
                            self::parseKeyAndInsertValueInArray($parsedBody, $part->getName(), $part->getBody());
                        }
                    }
                }
            }
        }
        $server = [
            'SERVER_PROTOCOL' => $protocolVersion,
            'REQUEST_METHOD' => $method,
            'REQUEST_TIME' => $event['requestContext']['requestTimeEpoch'] ?? time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'QUERY_STRING' => $queryString,
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_URI' => $uri,
        ];
        if (isset($headers['Host'])) {
            $server['HTTP_HOST'] = $headers['Host'];
        }

        return (new ServerRequest($method, $uri, $headers, $body, $protocolVersion, $server))
            ->withUploadedFiles($files)
            ->withCookieParams($cookies)
            ->withQueryParams($query)
            ->withParsedBody($parsedBody)
        ;
    }

    private static function createBodyStream(string $body): StreamInterface
    {
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $body);
        rewind($stream);

        return new Stream($stream);
    }
    /**
     * Parse a string key like "files[id_cards][jpg][]" and do $array['files']['id_cards']['jpg'][] = $value
     *
     * @param mixed $value
     */
    private static function parseKeyAndInsertValueInArray(array &$array, string $key, $value): void
    {
        if (strpos($key, '[') === false) {
            $array[$key] = $value;
            return;
        }
        $parts = explode('[', $key); // files[id_cards][jpg][] => [ 'files',  'id_cards]', 'jpg]', ']' ]
        $pointer = &$array;
        foreach ($parts as $k => $part) {
            if ($k === 0) {
                $pointer = &$pointer[$part];
                continue;
            }
            // Skip two special cases:
            // [[ in the key produces empty string
            // [test : starts with [ but does not end with ]
            if ($part === '' || substr($part, -1) !== ']') {
                // Malformed key, we use it "as is"
                $array[$key] = $value;
                return;
            }
            $part = substr($part, 0, -1); // The last char is a ] => remove it to have the real key
            if ($part === '') { // [] case
                $pointer = &$pointer[];
            } else {
                $pointer = &$pointer[$part];
            }
        }
        $pointer = $value;
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
