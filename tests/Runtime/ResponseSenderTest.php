<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Runtime\ResponseSender;
use Bref\Runtime\ResponseTooBig;
use Bref\Runtime\StreamedResponse;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Edge cases of the Runtime API, played by a server in another PHP process.
 */
class ResponseSenderTest extends TestCase
{
    private const TOO_BIG = "HTTP/1.1 413 Request Entity Too Large\r\nContent-Type: application/json\r\nContent-Length: 109\r\nConnection: close\r\n\r\n"
        . '{"errorMessage":"Exceeded maximum allowed payload size (6291556 bytes).","errorType":"RequestEntityTooLarge"}';

    /** @var resource|null */
    private $server = null;

    public function tearDown(): void
    {
        if ($this->server) {
            proc_terminate($this->server);
            proc_close($this->server);
        }
    }

    public function test the answer of the runtime API is read when it stops reading the response()
    {
        // Like the Runtime API with a response over 6 MB: it answers 413 and closes the connection.
        // The answer stays readable after the server closed with unread data (Linux and macOS keep it).
        $sender = new ResponseSender($this->startServer(self::TOO_BIG, false), '1', 'text/plain');

        $chunk = str_repeat('x', 1024 * 1024);
        for ($i = 0; $i < 20; $i++) {
            // Never throws, even once the connection is closed
            $sender->write($chunk);
        }

        $this->expectException(ResponseTooBig::class);
        $sender->end();
    }

    public function test chunks are no longer pulled once the runtime API stopped reading()
    {
        $sender = new ResponseSender($this->startServer(self::TOO_BIG, false), '1', 'text/plain');
        $pulled = 0;
        $response = new StreamedResponse('text/plain', (function () use (&$pulled) {
            for ($i = 0; $i < 100; $i++) {
                $pulled++;
                yield str_repeat('x', 1024 * 1024);
            }
        })());

        $response->writeTo($sender->write(...));

        $this->assertLessThan(100, $pulled);
    }

    public function test a refused response can still be reported with the error endpoint()
    {
        $sender = new ResponseSender($this->startServer("HTTP/1.1 400 Bad Request\r\nContent-Length: 49\r\n\r\n" . '{"errorType":"Fake","errorMessage":"Bad request"}', true), '1', 'text/plain');
        $sender->write('Hello');

        $this->assertSame('Error 400 while calling the Lambda runtime API: Fake: Bad request', $this->endError($sender));
        $this->assertFalse($sender->started());
    }

    public function test without an answer the error endpoint is not used()
    {
        // The connection closes without an answer: the response may have been received
        $sender = new ResponseSender($this->startServer('', true), '1', 'text/plain');
        $sender->write('Hello');

        $this->assertSame('No answer from the Lambda runtime API to the response', $this->endError($sender));
        $this->assertTrue($sender->started());
    }

    public function test abort ignores the failure of the end of the response()
    {
        $sender = new ResponseSender($this->startServer(self::TOO_BIG, true), '1', 'text/plain');
        $sender->write('Hello');

        // The original error is the one to report: no exception
        $sender->abort(new RuntimeException('Failure in the middle'));

        $this->assertTrue($sender->started());
    }

    public function test a connection failure is thrown by end()
    {
        // Nothing listens on that port
        $sender = new ResponseSender('127.0.0.1:1', '1', 'text/plain');

        $this->assertFalse($sender->write('Hello'));

        $this->assertFalse($sender->started());
        $this->expectExceptionMessage('Error while calling the Lambda runtime API');
        $sender->end();
    }

    private function endError(ResponseSender $sender): ?string
    {
        try {
            $sender->end();
        } catch (\Throwable $e) {
            return $e->getMessage();
        }

        return null;
    }

    /**
     * A server that reads the request headers (and the body if $readBody), then sends $answer and closes the connection.
     */
    private function startServer(string $answer, bool $readBody): string
    {
        $script = sprintf(<<<'PHP'
            $server = stream_socket_server('tcp://127.0.0.1:0');
            echo stream_socket_get_name($server, false), "\n";
            $client = stream_socket_accept($server);
            // Headers
            while (($line = fgets($client)) !== false && $line !== "\r\n") {
            }
            // Body: chunks, then "0", trailers and an empty line
            if (%s) {
                while (($size = fgets($client)) !== false && trim($size) !== '0') {
                    fread($client, hexdec(trim($size)) + 2);
                }
                while (($line = fgets($client)) !== false && $line !== "\r\n") {
                }
            }
            fwrite($client, %s);
            fclose($client);
            PHP, $readBody ? 'true' : 'false', var_export($answer, true));
        $this->server = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w']], $pipes);

        return trim((string) fgets($pipes[1]));
    }
}
