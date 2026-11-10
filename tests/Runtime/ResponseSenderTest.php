<?php declare(strict_types=1);

namespace Bref\Test\Runtime;

use Bref\Runtime\ResponseSender;
use Bref\Runtime\ResponseTooBig;
use PHPUnit\Framework\TestCase;

class ResponseSenderTest extends TestCase
{
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
        // Like the Runtime API with a response over 6 MB: it answers 413 and closes the connection
        $address = $this->startServer('HTTP/1.1 413 Request Entity Too Large', '{"errorMessage":"Exceeded maximum allowed payload size (6291556 bytes).","errorType":"RequestEntityTooLarge"}');
        $sender = new ResponseSender($address, '1', 'text/plain');

        $chunk = str_repeat('x', 1024 * 1024);
        for ($i = 0; $i < 20; $i++) {
            // Never throws, even once the connection is closed
            $sender->write($chunk);
        }

        $this->expectException(ResponseTooBig::class);
        $sender->end();
    }

    public function test a connection failure is thrown by end()
    {
        // Nothing listens on that port
        $sender = new ResponseSender('127.0.0.1:1', '1', 'text/plain');

        $sender->write('Hello');

        $this->assertFalse($sender->started());
        $this->expectExceptionMessage('Error while calling the Lambda runtime API');
        $sender->end();
    }

    /**
     * A server that reads the request headers, then answers and closes the connection.
     */
    private function startServer(string $statusLine, string $body): string
    {
        $script = sprintf(<<<'PHP'
            $server = stream_socket_server('tcp://127.0.0.1:0');
            echo stream_socket_get_name($server, false), "\n";
            $client = stream_socket_accept($server);
            while (($line = fgets($client)) !== false && trim($line) !== '') {
            }
            $body = %s;
            fwrite($client, %s . "\r\nContent-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\nConnection: close\r\n\r\n" . $body);
            fclose($client);
            PHP, var_export($body, true), var_export($statusLine, true));
        $this->server = proc_open([PHP_BINARY, '-r', $script], [1 => ['pipe', 'w']], $pipes);

        return trim((string) fgets($pipes[1]));
    }
}
