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
     */
    public function __construct(
        public readonly string $contentType,
        public readonly iterable $chunks,
    ) {
    }
}
