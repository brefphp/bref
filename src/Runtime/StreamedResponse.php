<?php declare(strict_types=1);

namespace Bref\Runtime;

use Closure;

/**
 * A response sent to Lambda chunk by chunk, as it is produced.
 *
 * Handlers return it instead of a value to encode as JSON. The chunks are either pulled (an iterable,
 * e.g. the output of PHP-FPM) or pushed (a function that writes them, e.g. from a framework's output buffer).
 */
final class StreamedResponse
{
    /**
     * @param iterable<string>|Closure(callable(string): void): void $chunks
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
    public function writeTo(callable $write): void
    {
        self::writeChunks($this->chunks, $write);
    }

    /**
     * @param iterable<string>|Closure(callable(string): mixed): void $chunks
     * @param callable(string): mixed                                 $write  Returns false when the rest is not wanted: chunks are no longer pulled.
     */
    public static function writeChunks(iterable|Closure $chunks, callable $write): void
    {
        if ($chunks instanceof Closure) {
            $chunks($write);
            return;
        }
        foreach ($chunks as $chunk) {
            if ($write($chunk) === false) {
                return;
            }
        }
    }
}
