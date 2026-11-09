<?php declare(strict_types=1);

namespace Bref\Event\Http;

/**
 * A PSR-7 body that produces its content by writing it (e.g. a framework's streamed response that
 * echoes its output): Bref sends each chunk to the client as soon as it is written.
 */
interface PushStream
{
    /**
     * Produce the content, passing each chunk to $write as it is produced.
     *
     * @param callable(string): void $write
     */
    public function writeTo(callable $write): void;
}
