<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Runtime\InvocationResult;
use Bref\Runtime\ResponseStream;
use LogicException;
use Throwable;

/**
 * Writes the HTTP response of an invocation: the status and headers first, then the body, then end().
 *
 * The runtime gives it to the HTTP handler. It is streamed or buffered, depending on what invoked the function:
 *
 * - streamed (Function URLs in `RESPONSE_STREAM` mode, REST APIs in `STREAM` mode): each part is sent to
 *   the client as it is written,
 * - buffered (API Gateway, ALB, Function URLs in `BUFFERED` mode…): the response is sent at end(), as JSON
 *   in the format of the invoker.
 */
final class HttpResponseWriter
{
    /**
     * The content type of Lambda's streamed HTTP responses.
     *
     * @internal Used by the Bref runtime.
     */
    public const STREAMED_CONTENT_TYPE = 'application/vnd.awslambda.http-integration-response';

    private ?int $statusCode = null;
    /** @var array<string|string[]> */
    private array $headers = [];
    /** The body of a buffered response, kept until end(). */
    private string $bufferedBody = '';
    /** Streamed responses: the status and headers are sent with the first byte of the body, see reset(). */
    private bool $statusSent = false;
    private bool $ended = false;
    /** Why the response could not be ended (e.g. it is too big), thrown again by the next end(). */
    private ?Throwable $endFailure = null;

    /**
     * @param mixed $event The event of the invocation: it tells the format of a buffered response.
     *
     * @internal The Bref runtime creates the writer and gives it to the handler.
     */
    public function __construct(
        private readonly ResponseStream $lambdaResponse,
        private readonly mixed $event,
        private readonly bool $streamed,
        private readonly ?string $awsRequestId = null,
    ) {
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

        if ($this->streamed) {
            return $this->sendStatus() && $this->lambdaResponse->write($chunk);
        }

        $this->bufferedBody .= $chunk;

        return true;
    }

    /**
     * End the response: the client gets it now, while the code that follows runs before the next invocation
     * (e.g. terminate callbacks). Nothing can be written after. The runtime ends the response otherwise.
     */
    public function end(): void
    {
        if ($this->ended) {
            // The handler ended the response and caught the failure: the runtime still gets it
            if ($this->endFailure !== null) {
                throw $this->endFailure;
            }
            return;
        }
        if ($this->statusCode === null) {
            throw new LogicException('The HTTP handler did not write any response');
        }
        $this->ended = true;

        try {
            if (! $this->streamed) {
                $response = new HttpResponse($this->bufferedBody, $this->headers, $this->statusCode);
                // Like HttpRequestEvent, for any event: warmers are not HTTP events
                $isFormatV2 = (float) ($this->event['version'] ?? '1.0') === 2.0;
                $json = $isFormatV2
                    ? $response->toApiGatewayFormatV2($this->awsRequestId)
                    : $response->toApiGatewayFormat(isset($this->event['multiValueHeaders']), $this->awsRequestId);

                $this->lambdaResponse->write(InvocationResult::encodeJson($json));
            } else {
                // A response without a body
                $this->sendStatus();
            }

            $this->lambdaResponse->end();
        } catch (Throwable $e) {
            $this->endFailure = $e;
            throw $e;
        }
    }

    /**
     * Forget the response written so far, to write another one instead (e.g. an error page after a failure).
     *
     * @return bool False when it is too late: the response has ended, or it is streamed and its
     *              status and headers were sent (with the first byte of its body).
     */
    public function reset(): bool
    {
        if ($this->ended) {
            return false;
        }
        if (! $this->started()) {
            return true;
        }
        if ($this->statusSent) {
            return false;
        }
        $this->statusCode = null;
        $this->headers = [];
        $this->bufferedBody = '';

        return true;
    }

    /**
     * Sent with the first byte of the body, not by start(): until then, a failure can still be answered
     * with an error page (e.g. a streamed response that fails before it writes anything).
     *
     * @return bool False when the rest of the response is not wanted.
     */
    private function sendStatus(): bool
    {
        if ($this->statusSent) {
            return true;
        }
        $this->statusSent = true;
        // Lambda's streamed format: the status code and headers as JSON, 8 null bytes, then the body as is
        $prelude = (new HttpResponse('', $this->headers, (int) $this->statusCode))->toStreamedPrelude($this->awsRequestId);

        return $this->lambdaResponse->write($prelude);
    }

    /**
     * Write a whole response at once and end it.
     *
     * @param array<string|string[]> $headers
     */
    public function respond(string $body, array $headers = [], int $statusCode = 200): void
    {
        $this->start($statusCode, $headers);
        $this->write($body);
        $this->end();
    }

    /**
     * Write the output of $output (`echo`, `flush()`…) as the body, as it is produced. For example
     * `fn () => $symfonyResponse->sendContent()`.
     *
     * Its output can no longer be stopped once the rest of the body is not wanted: it is discarded.
     */
    public function capture(callable $output): void
    {
        // Checked here: thrown from the output buffer's handler, the error would come with the output printed
        if (! $this->started()) {
            throw new LogicException('Start the HTTP response before writing its body');
        }

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
}
