<?php declare(strict_types=1);

namespace Bref\Bridge\Psr7;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Riverline\MultiPartParser\Part;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Stream;
use Zend\Diactoros\UploadedFile;

/**
 * Creates PSR-7 requests.
 */
class RequestFactory
{
    /**
     * Create a PSR-7 server request from an AWS Lambda HTTP event.
     */
    public static function fromLambdaEvent(array $event): ServerRequestInterface
    {
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
         * ?array[key]=value gives ['array[key]' => 'value'] while we want ['array' => ['key' = > 'value']]
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
}
