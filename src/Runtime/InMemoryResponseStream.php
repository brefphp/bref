<?php declare(strict_types=1);

namespace Bref\Runtime;

/**
 * Keeps what is written instead of sending it to Lambda: to read a response without the runtime
 * (tests, `bref-local`).
 *
 * @internal
 */
final class InMemoryResponseStream implements ResponseStream
{
    /** @var string[] */
    public array $chunks = [];
    public bool $ended = false;

    public function write(string $chunk): bool
    {
        $this->chunks[] = $chunk;

        return true;
    }

    public function end(): void
    {
        $this->ended = true;
    }

    public function contents(): string
    {
        return implode('', $this->chunks);
    }
}
