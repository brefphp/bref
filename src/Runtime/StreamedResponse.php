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
