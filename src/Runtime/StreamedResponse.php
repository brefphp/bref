<?php declare(strict_types=1);

namespace Bref\Runtime;

use Closure;

/**
 * A response sent to Lambda chunk by chunk, as it is produced.
 *
 * Handlers return it instead of a value to encode as JSON.
 */
final class StreamedResponse
{
    /**
     * @param iterable<string>|Closure(callable(string): void): void $chunks            The chunks, either pulled (an iterable,
     *                   e.g. the output of PHP-FPM) or pushed (a function that writes them, e.g. a framework's output buffer).
     * @param bool                                                   $failuresInTrailer Open the response right away, so that a failure is always reported in its
     *                                                                                  trailers (counted in the metrics, never retried) rather than with the error
     *                                                                                  endpoint (retried for asynchronous invocations).
     */
    public function __construct(
        public readonly string $contentType,
        public readonly iterable|Closure $chunks,
        public readonly bool $failuresInTrailer = false,
    ) {
    }

    /**
     * Pass each chunk to $write, as it is produced.
     *
     * @param callable(string): void $write
     */
    public function writeTo(callable $write): void
    {
        if ($this->chunks instanceof Closure) {
            ($this->chunks)($write);
            return;
        }
        foreach ($this->chunks as $chunk) {
            $write($chunk);
        }
    }
}
