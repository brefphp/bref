<?php declare(strict_types=1);

namespace Bref\Test\Fixture;

use Bref\Runtime\ResponseStream;
use Closure;

/**
 * A response to Lambda for tests: each chunk written, and the end, are passed to functions.
 */
final class CallbackResponseStream implements ResponseStream
{
    /**
     * @param Closure(string): mixed $onWrite Return false to refuse the rest of the response.
     * @param Closure(): void|null   $onEnd
     */
    public function __construct(
        private readonly Closure $onWrite,
        private readonly ?Closure $onEnd = null,
    ) {
    }

    public function write(string $chunk): bool
    {
        return ($this->onWrite)($chunk) !== false;
    }

    public function end(): void
    {
        if ($this->onEnd) {
            ($this->onEnd)();
        }
    }
}
