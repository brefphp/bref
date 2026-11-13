<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Closure;
use LogicException;

/**
 * Writes an HTTP response for any invoker: the status and headers first, then the body.
 *
 * Streamed (Function URLs in `RESPONSE_STREAM` mode, REST APIs in `STREAM` mode), each part is sent to
 * the client as it is written. Buffered (other invokers), the response is kept, then returned in the
 * format of the invoker (see toApiGatewayFormat()).
 */
final class HttpResponseWriter
{
    /** The content type of Lambda's streamed HTTP responses. */
    public const STREAMED_CONTENT_TYPE = 'application/vnd.awslambda.http-integration-response';

    private ?int $statusCode = null;
    /** @var array<string|string[]> */
    private array $headers = [];
    /** The buffered body. */
    private string $body = '';

    /**
     * @param Closure(string): mixed|null $send Sends the streamed response to Lambda, null when buffered.
     */
    private function __construct(
        private ?Closure $send,
        private ?string $awsRequestId,
    ) {
    }

    /**
     * @param callable(string): mixed $send Sends a chunk of Lambda's streamed HTTP response, returns false
     *                                      when the rest is not wanted.
     */
    public static function streamed(callable $send, ?string $awsRequestId = null): self
    {
        return new self($send(...), $awsRequestId);
    }

    public static function buffered(?string $awsRequestId = null): self
    {
        return new self(null, $awsRequestId);
    }

    public function started(): bool
    {
        return $this->statusCode !== null;
    }

    /**
     * @param array<string|string[]> $headers
     */
    public function start(int $statusCode, array $headers): void
    {
        if ($this->started()) {
            throw new LogicException('The HTTP response has already started');
        }
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        if ($this->send) {
            // Lambda's streamed format: the status code and headers as JSON, 8 null bytes, then the body as is
            ($this->send)((new HttpResponse('', $headers, $statusCode))->toStreamedPrelude($this->awsRequestId));
        }
    }

    /**
     * @return bool False when the rest of the body is not wanted (e.g. it can no longer be sent): there is no
     *              point producing it.
     */
    public function write(string $chunk): bool
    {
        if (! $this->started()) {
            throw new LogicException('Start the HTTP response before writing its body');
        }
        if ($chunk === '') {
            return true;
        }
        if (! $this->send) {
            $this->body .= $chunk;
            return true;
        }

        return ($this->send)($chunk) !== false;
    }

    /**
     * Write the output of $output (`echo`, `flush()`…) as the body, as it is produced. For example
     * `fn () => $symfonyResponse->sendContent()`.
     *
     * Its output can no longer be stopped once the rest of the body is not wanted: it is discarded.
     */
    public function capture(callable $output): void
    {
        $level = ob_get_level();
        // Every output is written from the output buffer's handler itself: nothing in there may produce
        // output or use output buffering (PHP forbids it), which write() never does
        ob_start(function (string $buffer): string {
            $this->write($buffer);

            return '';
        }, 1);
        try {
            $output();
        } finally {
            // Including the buffers $output left open
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
        }
    }

    /**
     * The buffered response, in the format of the invoker of the event (API Gateway, ALB, Function URL).
     */
    public function toApiGatewayFormat(HttpRequestEvent $event): array
    {
        if ($this->send) {
            throw new LogicException('A streamed HTTP response has already been sent');
        }
        if ($this->statusCode === null) {
            throw new LogicException('The HTTP handler did not write any response');
        }
        $response = new HttpResponse($this->body, $this->headers, $this->statusCode);

        return $event->isFormatV2()
            ? $response->toApiGatewayFormatV2($this->awsRequestId)
            : $response->toApiGatewayFormat($event->hasMultiHeader(), $this->awsRequestId);
    }
}
