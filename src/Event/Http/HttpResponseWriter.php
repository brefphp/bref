<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Runtime\LambdaResult;
use Closure;
use LogicException;

/**
 * Writes an HTTP response for any invoker: the status and headers first, then the body, then end().
 *
 * Streamed (Function URLs in `RESPONSE_STREAM` mode, REST APIs in `STREAM` mode), each part is sent to
 * the client as it is written. Buffered (other invokers), the response is sent at end(), in the format of
 * the invoker.
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
    private bool $ended = false;

    /**
     * @param HttpRequestEvent|null $bufferedFor The event whose invoker gets the buffered response, null when streamed.
     */
    private function __construct(
        private Closure $send,
        private Closure $end,
        private ?HttpRequestEvent $bufferedFor,
        private ?string $awsRequestId,
    ) {
    }

    /**
     * @param callable(string): mixed $send Sends a chunk to Lambda, returns false when the rest is not wanted.
     * @param callable(): void|null   $end  Ends the response sent to Lambda.
     */
    public static function streamed(callable $send, ?callable $end = null, ?string $awsRequestId = null): self
    {
        return new self($send(...), ($end ?? static function (): void {
        })(...), null, $awsRequestId);
    }

    /**
     * @param callable(string): mixed $send Sends the response (JSON) to Lambda.
     * @param callable(): void|null   $end  Ends the response sent to Lambda.
     */
    public static function buffered(HttpRequestEvent $event, callable $send, ?callable $end = null, ?string $awsRequestId = null): self
    {
        return new self($send(...), ($end ?? static function (): void {
        })(...), $event, $awsRequestId);
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

        if (! $this->bufferedFor) {
            // Lambda's streamed format: the status code and headers as JSON, 8 null bytes, then the body as is
            ($this->send)((new HttpResponse('', $headers, $statusCode))->toStreamedPrelude($this->awsRequestId));
        }
    }

    /**
     * @return bool False when the rest of the body is not wanted (e.g. it can no longer be sent, or the response
     *              has ended): there is no point producing it.
     */
    public function write(string $chunk): bool
    {
        if (! $this->started()) {
            throw new LogicException('Start the HTTP response before writing its body');
        }
        if ($this->ended) {
            return false;
        }
        if ($chunk === '') {
            return true;
        }
        if ($this->bufferedFor) {
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
     * End the response: the client gets it now, while the code that follows runs before the next invocation
     * (e.g. terminate callbacks). Nothing can be written after.
     */
    public function end(): void
    {
        if ($this->ended) {
            return;
        }
        if (! $this->started()) {
            throw new LogicException('The HTTP handler did not write any response');
        }
        $this->ended = true;

        if ($this->bufferedFor) {
            $response = new HttpResponse($this->body, $this->headers, $this->statusCode);
            ($this->send)(LambdaResult::encodeJson($this->bufferedFor->isFormatV2()
                ? $response->toApiGatewayFormatV2($this->awsRequestId)
                : $response->toApiGatewayFormat($this->bufferedFor->hasMultiHeader(), $this->awsRequestId)));
        }
        ($this->end)();
    }
}
