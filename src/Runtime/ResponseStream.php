<?php declare(strict_types=1);

namespace Bref\Runtime;

/**
 * The response of an invocation, sent to Lambda as it is written.
 *
 * @internal
 */
interface ResponseStream
{
    /**
     * @return bool False when the rest is not wanted (e.g. it can no longer be sent, or the response
     *              has ended): there is no point producing it.
     */
    public function write(string $chunk): bool;

    /**
     * End the response now: the code that follows runs before the next invocation (e.g. cleanup work).
     * Nothing can be written after. The runtime ends the response otherwise.
     */
    public function end(): void;
}
