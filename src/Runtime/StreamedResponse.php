<?php declare(strict_types=1);

namespace Bref\Runtime;

/**
 * A response sent to Lambda chunk by chunk, as it is produced.
 *
 * Handlers return it instead of a value to encode as JSON.
 */
final class StreamedResponse
{
    /**
     * @param iterable<string> $chunks
     * @param bool             $failuresInTrailer Open the response right away, so that a failure is always reported in its
     *                                            trailers (counted in the metrics, never retried) rather than with the error
     *                                            endpoint (retried for asynchronous invocations).
     */
    public function __construct(
        public readonly string $contentType,
        public readonly iterable $chunks,
        public readonly bool $failuresInTrailer = false,
    ) {
    }
}
