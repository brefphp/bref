<?php declare(strict_types=1);

namespace Bref\Http;

use GuzzleHttp\Psr7\ServerRequest;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\Psr7\UploadedFile;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Riverline\MultiPartParser\StreamedPart;
use Symfony\Component\HttpFoundation\Request;

class LambdaRequest
{
    private $event;

    private function __construct()
    {
    }

    public static function create(array $event): self
    {
        $model = new self;
        $model->event = $event;

        return $model;
    }

    public function getRawEvent(): array
    {
        return $this->event;
    }

    /**
     * TODO this class needs some more work
     * Currently we only support GET request
     *
     * @experimental
     */
    public function getSymfonyRequest(): Request
    {
        if (! class_exists(Request::class)) {
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
        $request = Request::create($uri, $method, [], $this->getCookies($event), [], [], $requestBody);

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

        $request->headers->add($headers);

        return $request;
    }

    public function getPsr7Request(): RequestInterface
    {
        if (! class_exists(Request::class)) {
            throw new \RuntimeException('You need to Symfony HTTP foundation to use this function. Please run "composer require guzzlehttp/psr7".');
        }
        if (! class_exists(StreamedPart::class)) {
            throw new \RuntimeException('You need to Symfony HTTP foundation to use this function. Please run "composer require riverline/multipart-parser".');
        }

        $event = $this->event;
        $method = $event['httpMethod'] ?? 'GET';
        $query = [];
        $bodyString = $event['body'] ?? '';
        $parsedBody = null;
        $files = [];
        $uri = $event['requestContext']['resourcePath'] ?? '/';
        $headers = $event['headers'] ?? [];
        $protocolVersion = isset($event['requestContext']['protocol']) ? trim($event['requestContext']['protocol'], 'HTTP/') : '1.1';
        if ($event['isBase64Encoded'] ?? false) {
            $bodyString = base64_decode($bodyString);
        }
        $body = $this->createBodyStream($bodyString);

        $queryString = $this->getQueryString($event);
        parse_str($queryString, $query);
        $contentType = $headers['content-type'] ?? $headers['Content-Type'] ?? null;
        if ($method === 'POST' && $contentType !== null) {
            /** @var string $contentType */
            if ($contentType === 'application/x-www-form-urlencoded') {
                parse_str($bodyString, $parsedBody);
            } else {
                $stream = fopen('php://temp', 'r+');
                fwrite($stream, "Content-type: $contentType\r\n\r\n" . $bodyString);
                rewind($stream);

                $document = new StreamedPart($stream);
                if ($document->isMultiPart()) {
                    $parsedBody = [];
                    foreach ($document->getParts() as $part) {
                        if (!$part->isFile()) {
                            $this->parseKeyAndInsertValueInArray($parsedBody, $part->getName(), $part->getBody());
                            continue;
                        }

                        $tmpPath = tempnam(sys_get_temp_dir(), 'bref_upload_');
                        if ($tmpPath === false) {
                            throw new \RuntimeException('Unable to create a temporary directory');
                        }
                        file_put_contents($tmpPath, $part->getBody());
                        $file = new UploadedFile($tmpPath, filesize($tmpPath), UPLOAD_ERR_OK, $part->getFileName(), $part->getMimeType());
                        $this->parseKeyAndInsertValueInArray($files, $part->getName(), $file);
                    }
                }
            }
        }
        $server = [
            'SERVER_PROTOCOL' => $protocolVersion,
            'REQUEST_METHOD' => $method,
            'REQUEST_TIME' => $event['requestContext']['requestTimeEpoch'] ?? time(),
            'QUERY_STRING' => $queryString,
            'DOCUMENT_ROOT' => getcwd(),
            'REQUEST_URI' => $uri,
        ];

        if (isset($headers['Host'])) {
            $server['HTTP_HOST'] = $headers['Host'];
        }

        return (new ServerRequest($method, $uri, $headers, $body, $protocolVersion, $server))
            ->withUploadedFiles($files)
            ->withCookieParams($this->getCookies($event))
            ->withQueryParams($query)
            ->withParsedBody($parsedBody);
    }

    private function createBodyStream(string $body): StreamInterface
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $body);
        rewind($stream);

        return new Stream($stream);
    }

    /**
     * Parse a string key like "files[id_cards][jpg][]" and do $array['files']['id_cards']['jpg'][] = $value
     *
     * @param mixed $value
     */
    private function parseKeyAndInsertValueInArray(array &$array, string $key, $value): void
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
        if (isset($event['multiValueQueryStringParameters']) && is_array($event['multiValueQueryStringParameters'])) {
            $query = '';
            foreach ($event['multiValueQueryStringParameters'] as $key => $values) {
                foreach ($values as $value) {
                    if ($value === '') {
                        $query .= sprintf('%s&', $key);
                    } else {
                        $query .= sprintf('%s=%s&', $key, $value);
                    }
                }
            }

            return rtrim($query, '&');
        }

        if (empty($event['queryStringParameters'])) {
            return '';
        }

        /*
         * Watch out in the future if using $event['queryStringParameters'] directly!
         *
         * queryStringParameters does not handle correctly arrays in parameters
         * ?array[key]=value gives ['array[key]' => 'value'] while we want ['array' => ['key' = > 'value']]
         * In that case we should recreate the original query string and use parse_str which handles correctly arrays
         */
        return urldecode(http_build_query($event['queryStringParameters']));
    }

    private function getCookies(array $event): array
    {
        $headers = $event['headers'];
        $cookies = [];
        if (isset($headers['Cookie'])) {
            $cookieParts = explode('; ', $headers['Cookie']);
            foreach ($cookieParts as $cookiePart) {
                [$cookieName, $cookieValue] = explode('=', $cookiePart, 2);
                $cookies[$cookieName] = urldecode($cookieValue);
            }
        }

        return $cookies;
    }
}
