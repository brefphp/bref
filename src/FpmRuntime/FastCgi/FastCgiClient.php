<?php declare(strict_types=1);

namespace Bref\FpmRuntime\FastCgi;

use Generator;
use RuntimeException;

/**
 * Minimal FastCGI client: sends one request to PHP-FPM and yields its output as PHP-FPM writes it.
 *
 * See https://fastcgi-archives.github.io/FastCGI_Specification.html
 *
 * @internal
 */
final class FastCgiClient
{
    private const BEGIN_REQUEST = 1;
    private const END_REQUEST = 3;
    private const PARAMS = 4;
    private const STDIN = 5;
    private const STDOUT = 6;
    private const RESPONDER = 1;
    private const MAX_RECORD_SIZE = 65535;

    public function __construct(private string $socketPath)
    {
    }

    /**
     * @param array<string, string|int> $params
     * @return Generator<int, string> The STDOUT of PHP-FPM (HTTP headers included), chunk by chunk.
     * @throws TimedOut If PHP-FPM has not finished writing the response before the deadline.
     * @throws RuntimeException
     */
    public function request(array $params, string $body, float $deadline): Generator
    {
        $socket = @stream_socket_client('unix://' . $this->socketPath, $errorCode, $errorMessage, 1);
        if ($socket === false) {
            throw new RuntimeException("Cannot connect to PHP-FPM: $errorMessage");
        }

        try {
            $encodedParams = '';
            foreach ($params as $name => $value) {
                $encodedParams .= $this->encodeLength(strlen($name)) . $this->encodeLength(strlen((string) $value)) . $name . $value;
            }
            $this->write($socket, $this->record(self::BEGIN_REQUEST, pack('nCx5', self::RESPONDER, 0))
                . $this->stream(self::PARAMS, $encodedParams)
                . $this->stream(self::STDIN, $body));

            while (true) {
                $header = unpack('Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength', $this->read($socket, 8, $deadline));
                $content = $this->read($socket, $header['contentLength'] + $header['paddingLength'], $deadline);
                if ($header['type'] === self::END_REQUEST) {
                    return;
                }
                // STDERR records are ignored: PHP-FPM writes the logs of the application to its own stderr
                if ($header['type'] === self::STDOUT && $header['contentLength'] > 0) {
                    yield substr($content, 0, $header['contentLength']);
                }
            }
        } finally {
            // Closing the connection also stops PHP-FPM writing a response nobody will read
            fclose($socket);
        }
    }

    private function record(int $type, string $content): string
    {
        return pack('CCnnCx', 1, $type, 1, strlen($content), 0) . $content;
    }

    /**
     * A stream of records, terminated by an empty record.
     */
    private function stream(int $type, string $content): string
    {
        $records = '';
        foreach (str_split($content, self::MAX_RECORD_SIZE) as $part) {
            $records .= $this->record($type, $part);
        }

        return $records . $this->record($type, '');
    }

    private function encodeLength(int $length): string
    {
        return $length < 128 ? chr($length) : pack('N', $length | 0x80000000);
    }

    /**
     * @param resource $socket
     */
    private function write($socket, string $data): void
    {
        while ($data !== '') {
            $written = fwrite($socket, $data);
            if ($written === false || $written === 0) {
                throw new RuntimeException('Failed to write the request to PHP-FPM');
            }
            $data = substr($data, $written);
        }
    }

    /**
     * @param resource $socket
     */
    private function read($socket, int $length, float $deadline): string
    {
        $data = '';
        while (strlen($data) < $length) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                throw new TimedOut;
            }
            stream_set_timeout($socket, (int) $remaining, (int) (fmod($remaining, 1) * 1_000_000));
            $chunk = fread($socket, $length - strlen($data));
            if (stream_get_meta_data($socket)['timed_out']) {
                throw new TimedOut;
            }
            if ($chunk === false || ($chunk === '' && feof($socket))) {
                throw new RuntimeException('PHP-FPM closed the connection before the end of the response');
            }
            $data .= $chunk;
        }

        return $data;
    }
}
