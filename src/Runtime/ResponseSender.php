<?php declare(strict_types=1);

namespace Bref\Runtime;

use Exception;
use Throwable;

/**
 * One invocation response, sent to the Runtime API with its streaming mode: a chunked HTTP request,
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
    /** @var resource|null */
    private $socket = null;
    private bool $started = false;
    private bool $ended = false;
    /** The Runtime API stopped reading the response (e.g. too big): its answer, read by end(), explains why. */
    private bool $stoppedReading = false;
    private ?Throwable $writeError = null;

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
     * Never throws: it can run inside a framework's output handler (pushed bodies), where an exception
     * would skip the end of the request (e.g. terminate callbacks). After a failure, the rest of the
     * body is dropped and end() throws the failure.
     */
    public function write(string $chunk): void
    {
        if ($chunk === '' || $this->stoppedReading || $this->writeError !== null) {
            return;
        }
        try {
            $this->open();
            $this->stoppedReading = ! $this->send(dechex(strlen($chunk)) . "\r\n" . $chunk . "\r\n");
        } catch (Throwable $e) {
            $this->writeError = $e;
        }
    }

    /**
     * End the response, with an error in its trailers if any (counted in the function's error metrics),
     * and check the Runtime API accepted it.
     *
     * @throws ResponseTooBig
     * @throws Exception
     */
    public function end(?Throwable $error = null): void
    {
        if ($this->writeError !== null) {
            throw $this->writeError;
        }
        $this->open();
        $this->ended = true;

        $end = "0\r\n";
        if ($error) {
            $end .= 'Lambda-Runtime-Function-Error-Type: ' . get_class($error) . "\r\n"
                . 'Lambda-Runtime-Function-Error-Body: ' . base64_encode((string) json_encode([
                    'errorType' => get_class($error),
                    'errorMessage' => $error->getMessage(),
                ], JSON_INVALID_UTF8_SUBSTITUTE)) . "\r\n";
        }
        $this->send($end . "\r\n");

        [$statusCode, $errorMessage] = $this->readAnswer();
        if ($statusCode === 413) {
            throw new ResponseTooBig;
        }
        if ($statusCode < 200 || $statusCode >= 300) {
            // The response was refused: the error endpoint is the way to end the invocation
            $this->started = false;
            throw new Exception("Error $statusCode while calling the Lambda runtime API: $errorMessage");
        }
        if ($this->stoppedReading) {
            throw new Exception('The Lambda runtime API stopped reading the response before its end');
        }
    }

    /**
     * End a response that failed in the middle: the error goes in its trailers.
     */
    public function abort(Throwable $error): void
    {
        if (! $this->started || $this->ended) {
            return;
        }
        $this->writeError = null;
        try {
            $this->end($error);
        } catch (Throwable) {
            // The original error is the one to report
        }
    }

    private function open(): void
    {
        if ($this->socket !== null) {
            return;
        }
        $socket = @stream_socket_client("tcp://$this->apiUrl", $errorCode, $errorMessage, 5);
        if ($socket === false) {
            throw new Exception("Error while calling the Lambda runtime API: $errorMessage");
        }
        $this->socket = $socket;
        $this->started = true;
        $this->send("POST /2018-06-01/runtime/invocation/$this->invocationId/response HTTP/1.1\r\n"
            . "Host: $this->apiUrl\r\n"
            . "Content-Type: $this->contentType\r\n"
            . "Lambda-Runtime-Function-Response-Mode: streaming\r\n"
            . "Transfer-Encoding: chunked\r\n"
            . "Trailer: Lambda-Runtime-Function-Error-Type, Lambda-Runtime-Function-Error-Body\r\n"
            . "\r\n");
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

    /**
     * @return array{int, string} The status code, and the error message if any.
     */
    private function readAnswer(): array
    {
        // The Runtime API answers right away: never wait for it until the end of the invocation
        stream_set_timeout($this->socket, 5);
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
        fclose($this->socket);

        $error = json_decode($body, true);
        $errorMessage = isset($error['errorType'], $error['errorMessage'])
            ? "{$error['errorType']}: {$error['errorMessage']}"
            : ($body ?: trim($statusLine));

        return [(int) (explode(' ', $statusLine)[1] ?? 0), $errorMessage];
    }
}
