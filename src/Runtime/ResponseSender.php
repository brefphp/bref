<?php declare(strict_types=1);

namespace Bref\Runtime;

use Exception;
use Throwable;

/**
 * Sends one invocation response to the Runtime API with its streaming mode: a chunked HTTP request,
 * written on a socket as the chunks come.
 *
 * The request is opened with the first byte: until then, a failure can still be reported with
 * the error endpoint of the Runtime API (see started()).
 *
 * @internal
 * @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html#runtimes-custom-response-streaming
 */
final class ResponseSender
{
    /**
     * The Runtime API is local and reads as fast as we write, then answers right away:
     * never wait for it until the end of the invocation.
     */
    private const TIMEOUT_IN_SECONDS = 10;

    /** @var resource|null */
    private $socket = null;
    private bool $started = false;
    private bool $ended = false;
    /** Why the rest of the response is dropped (e.g. the Runtime API stopped reading it), reported by end(). */
    private ?string $failure = null;
    /** The connection to the Runtime API failed: nothing was sent. */
    private ?Throwable $connectionError = null;

    public function __construct(
        private string $apiUrl,
        private string $invocationId,
        private string $contentType,
    ) {
    }

    /**
     * Whether the response has started: from then on, the Runtime API refuses the error endpoint for this invocation.
     */
    public function started(): bool
    {
        return $this->started;
    }

    /**
     * Never throws for a string chunk: it can run inside a framework's output handler (pushed bodies), where
     * an exception would skip the end of the request (e.g. terminate callbacks). After a failure, the rest of
     * the body is dropped and end() reports the failure.
     *
     * @return bool False when the rest of the body is dropped: there is no point producing it.
     */
    public function write(string $chunk): bool
    {
        if ($this->failure !== null || $this->connectionError !== null) {
            return false;
        }
        if ($chunk === '') {
            return true;
        }
        try {
            $this->open();
        } catch (Throwable $e) {
            $this->connectionError = $e;
            return false;
        }
        if (! $this->send(dechex(strlen($chunk)) . "\r\n" . $chunk . "\r\n")) {
            // The Runtime API stops reading e.g. when the response is too big: its answer, read by end(), says why
            $this->failure = $this->timedOut()
                ? 'Writing the response to the Lambda runtime API timed out'
                : 'The Lambda runtime API stopped reading the response before its end';
            return false;
        }

        return true;
    }

    /**
     * End the response and check the Runtime API accepted it.
     *
     * @param Throwable|null $error Reported in the trailers of the response (counted in the function's error metrics).
     * @throws ResponseTooBig
     * @throws Exception
     */
    public function end(?Throwable $error = null): void
    {
        if ($this->connectionError !== null) {
            throw $this->connectionError;
        }
        $this->open();
        $this->finish($error);
    }

    /**
     * End a response that failed in the middle: the error goes in its trailers.
     */
    public function abort(Throwable $error): void
    {
        if (! $this->started || $this->ended) {
            return;
        }
        try {
            $this->finish($error);
        } catch (Throwable) {
            // The original error is the one to report
        }
    }

    /**
     * @throws ResponseTooBig
     * @throws Exception
     */
    private function finish(?Throwable $error): void
    {
        $this->ended = true;

        $end = "0\r\n";
        if ($error) {
            // Without the stack trace that the error endpoint gets: trailers are meant to stay small
            $end .= 'Lambda-Runtime-Function-Error-Type: ' . get_class($error) . "\r\n"
                . 'Lambda-Runtime-Function-Error-Body: ' . base64_encode((string) json_encode([
                    'errorType' => get_class($error),
                    'errorMessage' => $error->getMessage(),
                ], JSON_INVALID_UTF8_SUBSTITUTE)) . "\r\n";
        }
        $this->send($end . "\r\n");

        [$statusCode, $errorMessage] = $this->readAnswer();
        if ($statusCode === 0) {
            // The response may have been received: never try the error endpoint for this invocation
            throw new Exception('No answer from the Lambda runtime API to the response');
        }
        if ($statusCode === 413) {
            throw new ResponseTooBig;
        }
        if ($statusCode < 200 || $statusCode >= 300) {
            // The response was refused: the error endpoint is the way to end the invocation
            $this->started = false;
            throw new Exception("Error $statusCode while calling the Lambda runtime API: $errorMessage");
        }
        if ($this->failure !== null) {
            throw new Exception($this->failure);
        }
    }

    /**
     * @throws Exception
     */
    private function open(): void
    {
        if ($this->socket !== null) {
            return;
        }
        $socket = @stream_socket_client("tcp://$this->apiUrl", $errorCode, $errorMessage, self::TIMEOUT_IN_SECONDS);
        if ($socket === false) {
            throw new Exception("Error while calling the Lambda runtime API: $errorMessage");
        }
        // For writes and reads (the connection timeout above is only for connecting)
        stream_set_timeout($socket, self::TIMEOUT_IN_SECONDS);
        $this->socket = $socket;
        $sent = $this->send("POST /2018-06-01/runtime/invocation/$this->invocationId/response HTTP/1.1\r\n"
            . "Host: $this->apiUrl\r\n"
            . "Content-Type: $this->contentType\r\n"
            . "Lambda-Runtime-Function-Response-Mode: streaming\r\n"
            . "Transfer-Encoding: chunked\r\n"
            . "Trailer: Lambda-Runtime-Function-Error-Type, Lambda-Runtime-Function-Error-Body\r\n"
            . "\r\n");
        if (! $sent) {
            fclose($socket);
            $this->socket = null;
            throw new Exception('Error while calling the Lambda runtime API: the request could not be sent');
        }
        $this->started = true;
    }

    private function send(string $data): bool
    {
        while ($data !== '') {
            $written = @fwrite($this->socket, $data);
            if ($written === false || $written === 0) {
                return false;
            }
            $data = substr($data, $written);
        }

        return true;
    }

    private function timedOut(): bool
    {
        return stream_get_meta_data($this->socket)['timed_out'];
    }

    /**
     * Reads the answer of the Runtime API, which always has a Content-Length (chunked answers are read too,
     * for test servers; chunk extensions and answers delimited by closing the connection are not supported).
     *
     * @return array{int, string} The status code (0 without an answer), and the error message if any.
     */
    private function readAnswer(): array
    {
        $statusLine = (string) fgets($this->socket);
        $contentLength = 0;
        $chunked = false;
        while (($line = fgets($this->socket)) !== false && trim($line) !== '') {
            if (stripos($line, 'content-length:') === 0) {
                $contentLength = (int) trim(substr($line, strlen('content-length:')));
            }
            $chunked = $chunked || preg_match('/^transfer-encoding:\s*chunked/i', $line) === 1;
        }
        $body = '';
        if ($chunked) {
            while (($size = hexdec(trim((string) fgets($this->socket)))) > 0) {
                $body .= stream_get_contents($this->socket, (int) $size);
                fgets($this->socket);
            }
        } elseif ($contentLength > 0) {
            $body = (string) stream_get_contents($this->socket, $contentLength);
        }
        $statusCode = (int) (explode(' ', $statusLine)[1] ?? 0);
        fclose($this->socket);

        $error = json_decode($body, true);
        $errorMessage = isset($error['errorType'], $error['errorMessage'])
            ? "{$error['errorType']}: {$error['errorMessage']}"
            : ($body ?: trim($statusLine));

        return [$statusCode, $errorMessage];
    }
}
