<?php declare(strict_types=1);

namespace Bref\Runtime;

use Closure;
use Exception;

/**
 * The result of an invocation, sent to Lambda chunk by chunk as it is produced (whatever the event: HTTP or not).
 *
 * Handlers return it instead of a value to encode as JSON. The chunks are either pulled (an iterable,
 * e.g. the output of PHP-FPM) or pushed (a function that writes them, e.g. an HTTP response writer).
 */
final class LambdaResult
{
    /**
     * @param iterable<string>|Closure(callable(string): mixed, callable(): void): void $chunks A function receives
     *     the function that writes a chunk, and the function that ends the result early (the rest of its code
     *     runs before the next invocation, e.g. cleanup work).
     */
    public function __construct(
        public readonly string $contentType,
        public readonly iterable|Closure $chunks,
    ) {
    }

    /**
     * Pass each chunk to $write, as it is produced. An iterable is single-use: a generator cannot be sent twice.
     *
     * @param callable(string): mixed $write Returns false when the rest is not wanted: chunks are no longer pulled.
     */
    public function writeTo(callable $write, ?callable $end = null): void
    {
        self::writeChunks($this->chunks, $write, $end);
    }

    /**
     * @param iterable<string>|Closure(callable(string): mixed, callable(): void): void $chunks
     * @param callable(string): mixed                                                   $write  Returns false when the rest is not wanted: chunks are no longer pulled.
     * @param callable(): void|null                                                     $end    Ends the result early.
     */
    public static function writeChunks(iterable|Closure $chunks, callable $write, ?callable $end = null): void
    {
        if ($chunks instanceof Closure) {
            $chunks($write, $end ?? static function (): void {
            });
            return;
        }
        foreach ($chunks as $chunk) {
            if ($write($chunk) === false) {
                return;
            }
        }
    }

    /**
     * A value encoded as JSON, the result of most invocations.
     *
     * @throws Exception If the value cannot be encoded.
     */
    public static function json(mixed $value): self
    {
        return new self('application/json', [self::encodeJson($value)]);
    }

    /**
     * @throws Exception If the value cannot be encoded.
     */
    public static function encodeJson(mixed $value): string
    {
        /** @noinspection JsonEncodingApiUsageInspection */
        $json = json_encode($value);
        if ($json === false) {
            throw new Exception(sprintf(
                "The Lambda response cannot be encoded to JSON.\nThis error usually happens when you try to return binary content. If you are writing an HTTP application and you want to return a binary HTTP response (like an image, a PDF, etc.), please read this guide: https://bref.sh/docs/runtimes/http.html#binary-requests-and-responses\nHere is the original JSON error: '%s'",
                json_last_error_msg()
            ));
        }

        return $json;
    }

    /**
     * The whole result at once (e.g. in tests). Like writeTo(), it can only be used once.
     */
    public function contents(): string
    {
        $contents = '';
        $this->writeTo(function (string $chunk) use (&$contents): void {
            $contents .= $chunk;
        });

        return $contents;
    }
}
