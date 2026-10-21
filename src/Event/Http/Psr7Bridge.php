<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use LogicException;
use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Stream;
use Nyholm\Psr7\UploadedFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Riverline\MultiPartParser\Part;
use RuntimeException;

use function str_starts_with;

/**
 * Bridges PSR-7 requests and responses with API Gateway or ALB event/response formats.
 */
final class Psr7Bridge
{
    private const UPLOADED_FILES_PREFIX = 'bref_upload_';

    /**
     * Create a PSR-7 server request from an AWS Lambda HTTP event.
     */
    public static function convertRequest(HttpRequestEvent $event, Context $context): ServerRequestInterface
    {
        $headers = $event->getHeaders();

        [$files, $parsedBody] = self::parseBodyAndUploadedFiles($event);
        [$user, $password] = $event->getBasicAuthCredentials();

        $server = array_filter([
            'CONTENT_LENGTH' => $headers['content-length'][0] ?? null,
            'CONTENT_TYPE' => $event->getContentType(),
            'DOCUMENT_ROOT' => getcwd(),
            'QUERY_STRING' => $event->getQueryString(),
            'REQUEST_METHOD' => $event->getMethod(),
            'SERVER_NAME' => $event->getServerName(),
            'SERVER_PORT' => $event->getServerPort(),
            'SERVER_PROTOCOL' => $event->getProtocol(),
            'PATH_INFO' => $event->getPath(),
            'HTTP_HOST' => $headers['host'] ?? null,
            'REMOTE_ADDR' => $event->getSourceIp(),
            'REMOTE_PORT' => $event->getRemotePort(),
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
            'REQUEST_URI' => $event->getUri(),
            'PHP_AUTH_USER' => $user,
            'PHP_AUTH_PW' => $password,
        ], fn ($value) => $value !== null);

        foreach ($headers as $name => $values) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', (string) $name))] = $values[0];
        }

        /**
         * Nyholm/psr7 does not rewind body streams, we do it manually
         * so that users can fetch the content of the body directly.
         */
        $bodyStream = Stream::create($event->getBody());
        $bodyStream->rewind();

        $request = new ServerRequest(
            $event->getMethod(),
            $event->getUri(),
            $event->getHeaders(),
            $bodyStream,
            $event->getProtocolVersion(),
            $server
        );

        foreach ($event->getPathParameters() as $key => $value) {
            $request = $request->withAttribute($key, $value);
        }

        return $request->withUploadedFiles($files)
            ->withCookieParams($event->getCookies())
            ->withQueryParams($event->getQueryParameters())
            ->withParsedBody($parsedBody)
            ->withAttribute('lambda-event', $event)
            ->withAttribute('lambda-context', $context);
    }

    /**
     * Create a ALB/API Gateway response from a PSR-7 response.
     */
    public static function convertResponse(ResponseInterface $response): HttpResponse
    {
        $response->getBody()->rewind();
        $body = $response->getBody()->getContents();

        return new HttpResponse($body, $response->getHeaders(), $response->getStatusCode());
    }

    /**
     * @return array{0: array<array-key, mixed>, 1: array<array-key, mixed>|null} The uploaded files (UploadedFile instances, nested like the form keys) and the parsed body
     */
    private static function parseBodyAndUploadedFiles(HttpRequestEvent $event): array
    {
        $contentType = $event->getContentType();
        if ($contentType === null || $event->getMethod() !== 'POST') {
            return [[], null];
        }

        if (str_starts_with($contentType, 'application/x-www-form-urlencoded')) {
            $parsedBody = [];
            parse_str($event->getBody(), $parsedBody);
            return [[], $parsedBody];
        }

        try {
            return self::parseMultipartBody($contentType, $event->getBody());
        } catch (LogicException) {
            // The parser throws on malformed bodies (empty form, missing boundary, truncated body…)
            // PHP ignores these instead of failing the request, so we do the same
            return [[], null];
        }
    }

    /**
     * @return array{0: array<array-key, mixed>, 1: array<array-key, mixed>|null} The uploaded files (UploadedFile instances, nested like the form keys) and the parsed body
     */
    private static function parseMultipartBody(string $contentType, string $body): array
    {
        $document = new Part("Content-type: $contentType\r\n\r\n" . $body);
        if (! $document->isMultiPart()) {
            return [[], null];
        }
        $fields = [];
        $fileFields = [];
        $uploadedFiles = [];
        foreach ($document->getParts() as $part) {
            $name = $part->getName();
            if ($name === null) {
                // PHP ignores parts without a name
                continue;
            }
            if ($part->isFile()) {
                $tmpPath = tempnam(sys_get_temp_dir(), self::UPLOADED_FILES_PREFIX);
                if ($tmpPath === false) {
                    throw new RuntimeException('Unable to create a temporary directory');
                }
                file_put_contents($tmpPath, $part->getBody());
                // The value stands for the file until the keys are parsed below
                $fileFields[] = urlencode($name) . '=' . count($uploadedFiles);
                $uploadedFiles[] = new UploadedFile($tmpPath, filesize($tmpPath), UPLOAD_ERR_OK, $part->getFileName(), $part->getMimeType());
            } else {
                $fields[] = urlencode($name) . '=' . urlencode($part->getBody());
            }
        }

        // All the keys are parsed in one go, in order, with parse_str: the same way PHP does natively.
        // Parsing them one at a time and merging the results with array_merge_recursive renumbered integer keys:
        // "users[0][id]" and "users[0][name]" ended up in two different array items.
        $parsedBody = null;
        if ($fields !== []) {
            $parsedBody = [];
            parse_str(implode('&', $fields), $parsedBody);
        }
        $files = [];
        if ($fileFields !== []) {
            parse_str(implode('&', $fileFields), $files);
            array_walk_recursive($files, function (&$value) use ($uploadedFiles): void {
                $value = $uploadedFiles[(int) $value];
            });
        }
        return [$files, $parsedBody];
    }

    /**
     * Cleanup previously uploaded files.
     */
    public static function cleanupUploadedFiles(): void
    {
        // tempnam() adds 6 random characters to the prefix before PHP 8.4, and 19 since
        $tmpFiles = glob(sys_get_temp_dir() . '/' . self::UPLOADED_FILES_PREFIX . '*');

        if ($tmpFiles !== false) {
            foreach ($tmpFiles as $file) {
                if (is_file($file)) {
                    // Silence warnings, we don't want to crash the whole runtime
                    @unlink($file);
                }
            }
        }
    }
}
