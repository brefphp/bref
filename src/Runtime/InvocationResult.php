<?php declare(strict_types=1);

namespace Bref\Runtime;

use Closure;
use Exception;

/**
 * The result of an invocation, written to the response sent to Lambda (whatever the event: HTTP or not).
 *
 * Handlers return it instead of a value to encode as JSON.
 *
 * @internal
 */
final class InvocationResult
{
    /**
     * @param Closure(ResponseStream): void $write Writes the result to the response. It runs once the runtime
     *     is ready to send the response.
     */
    public function __construct(
        public readonly string $contentType,
        private readonly Closure $write,
        /**
         * Start the response right away, so that a failure is always reported in its trailers (counted in the
         * error metrics, never retried) rather than with the error endpoint of the Runtime API (retried for
         * asynchronous invocations).
         */
        public readonly bool $failuresInTrailer = false,
    ) {
    }

    /**
     * A value encoded as JSON, the result of most invocations.
     *
     * @throws Exception If the value cannot be encoded.
     */
    public static function json(mixed $value): self
    {
        $json = self::encodeJson($value);

        return new self('application/json', static function (ResponseStream $response) use ($json): void {
            $response->write($json);
        });
    }

    /**
     * Can only be used once: the result is produced as it is written.
     */
    public function writeTo(ResponseStream $response): void
    {
        ($this->write)($response);
    }

    /**
     * @throws Exception If the value cannot be encoded.
     */
    public static function encodeJson(mixed $value, int $flags = 0): string
    {
        /** @noinspection JsonEncodingApiUsageInspection */
        $json = json_encode($value, $flags);
        if ($json === false) {
            throw new Exception(sprintf(
                "The Lambda response cannot be encoded to JSON.\nThis error usually happens when you try to return binary content. If you are writing an HTTP application and you want to return a binary HTTP response (like an image, a PDF, etc.), please read this guide: https://bref.sh/docs/runtimes/http.html#binary-requests-and-responses\nHere is the original JSON error: '%s'",
                json_last_error_msg()
            ));
        }

        return $json;
    }
}
