<?php declare(strict_types=1);

namespace Bref\Runtime;

use Bref\Http\LambdaResponse;
use Bref\Runtime\FastCgi\FastCgiCommunicationFailed;
use Bref\Runtime\FastCgi\FastCgiRequest;
use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use Symfony\Component\Process\Process;

/**
 * Proxies HTTP events coming from API Gateway to PHP-FPM via FastCGI.
 *
 * Usage example:
 *
 *     $event = [get the Lambda event];
 *     $phpFpm = new PhpFpm('index.php');
 *     $phpFpm->start();
 *     $lambdaResponse = $phpFpm->proxy($event);
 *     $phpFpm->stop();
 *     [send the $lambdaResponse];
 */
class PhpFpm
{
    private const SOCKET = '/tmp/.bref/php-fpm.sock';
    private const PID_FILE = '/tmp/.bref/php-fpm.pid';
    private const CONFIG = '/opt/bref/etc/php-fpm.conf';

    /** @var Client|null */
    private $client;
    /** @var string */
    private $handler;
    /** @var string */
    private $configFile;
    /** @var Process|null */
    private $fpm;

    public function __construct(string $handler, string $configFile = self::CONFIG)
    {
        $this->handler = $handler;
        $this->configFile = $configFile;
    }

    /**
     * Start the PHP-FPM process.
     */
    public function start(): void
    {
        // In case Lambda stopped our process (e.g. because of a timeout) we need to make sure PHP-FPM has stopped
        // as well and restart it
        if ($this->isReady()) {
            $this->killExistingFpm();
        }

        if (! is_dir(dirname(self::SOCKET))) {
            mkdir(dirname(self::SOCKET));
        }

        /**
         * --nodaemonize: we want to keep control of the process
         * --force-stderr: force logs to be sent to stderr, which will allow us to send them to CloudWatch
         */
        $this->fpm = new Process(['php-fpm', '--nodaemonize', '--force-stderr', '--fpm-config', $this->configFile]);
        $this->fpm->setTimeout(null);
        $this->fpm->start(function ($type, $output): void {
            // Send any PHP-FPM log to CloudWatch
            echo $output;
        });

        $connection = new UnixDomainSocket(self::SOCKET, 1000, 30000);
        $this->client = new Client($connection);

        $this->waitUntilReady();
    }

    public function stop(): void
    {
        if ($this->fpm && $this->fpm->isRunning()) {
            $this->fpm->stop(2);
            if ($this->isReady()) {
                throw new \Exception('PHP-FPM cannot be stopped');
            }
        }
    }

    public function __destruct()
    {
        $this->stop();
    }

    /**
     * @throws \Exception If the PHP-FPM process is not running anymore.
     */
    public function ensureStillRunning(): void
    {
        if (! $this->fpm || ! $this->fpm->isRunning()) {
            throw new \Exception('PHP-FPM has stopped for an unknown reason');
        }
    }
    /**
     * Return an array of the response headers.
     */
    private function getHeaders(ProvidesResponseData $response, bool $isMultiHeader): array
    {
        if ($isMultiHeader) {
            $responseHeaders = [];
            $lines  = explode(PHP_EOL, $response->getRawResponse());
            foreach ($lines as $i => $line) {
                if (preg_match('#^([^\:]+):(.*)$#', $line, $matches)) {
                    $key = trim($matches[1]);
                    if (! array_key_exists($key, $responseHeaders)) {
                        $responseHeaders[$key]= [];
                    }
                    $responseHeaders[$key][] = trim($matches[2]);
                    continue;
                }
                break;
            }
        } else {
            $responseHeaders = $response->getHeaders();
        }
        return array_change_key_case($responseHeaders, CASE_LOWER);
    }
    /**
     * Proxy the API Gateway event to PHP-FPM and return its response.
     *
     * @param mixed $event
     */
    public function proxy($event): LambdaResponse
    {
        if (! isset($event['httpMethod'])) {
            throw new \Exception('The lambda was not invoked via HTTP through API Gateway: this is not supported by this runtime');
        }

        $request = $this->eventToFastCgiRequest($event);

        try {
            $response = $this->client->sendRequest($request);
        } catch (\Throwable $e) {
            throw new FastCgiCommunicationFailed(sprintf(
                'Error communicating with PHP-FPM to read the HTTP response. A root cause of this can be that the Lambda (or PHP) timed out, for example when trying to connect to a remote API or database, if this happens continuously check for those! Original exception message: %s %s',
                get_class($e),
                $e->getMessage()
            ), 0, $e);
        }

        $isALB = array_key_exists('elb', $event['requestContext']);
        $responseHeaders = $this->getHeaders($response, $isALB);
        if (array_key_exists('status', $responseHeaders)) {
            $statscode = is_array($responseHeaders['status']) ? $responseHeaders['status'][0]: $responseHeaders['status'];
            $status = (int) preg_replace('/[^0-9]/', '', $statscode);
            unset($responseHeaders['status']);
        }

        return new LambdaResponse($status ?? 200, $responseHeaders, $response->getBody());
    }

    private function waitUntilReady(): void
    {
        $wait = 5000; // 5ms
        $timeout = 5000000; // 5 secs
        $elapsed = 0;

        while (! $this->isReady()) {
            usleep($wait);
            $elapsed += $wait;

            if ($elapsed > $timeout) {
                throw new \Exception('Timeout while waiting for PHP-FPM socket at ' . self::SOCKET);
            }

            // If the process has crashed we can stop immediately
            if (! $this->fpm->isRunning()) {
                throw new \Exception('PHP-FPM failed to start');
            }
        }
    }

    private function isReady(): bool
    {
        clearstatcache(false, self::SOCKET);

        return file_exists(self::SOCKET);
    }

    private function eventToFastCgiRequest(array $event): ProvidesRequestData
    {
        $requestBody = $event['body'] ?? '';
        if ($event['isBase64Encoded'] ?? false) {
            $requestBody = base64_decode($requestBody);
        }

        $method = strtoupper($event['httpMethod']);
        $request = new FastCgiRequest($method, $this->handler, $requestBody);

        $uri = $event['path'] ?? '/';
        /*
         * queryStringParameters does not handle correctly arrays in parameters
         * ?array[key]=value gives ['array[key]' => 'value'] while we want ['array' => ['key' = > 'value']]
         * We recreate the original query string and we use parse_str which handles correctly arrays
         *
         * There's still an issue: AWS API Gateway does not support multiple query string parameters with the same name
         * So you can't use something like ?array[]=val1&array[]=val2 because only the 'val2' value will survive
         */
        if (array_key_exists('multiValueQueryStringParameters', $event) && $event['multiValueQueryStringParameters']) {
            $queryParameters = [];
            foreach ($event['multiValueQueryStringParameters'] as $key => $value) {
                $queryParameters[$key] = $value[0];
            }
            if ($queryParameters) {
                $uri .= '?' . http_build_query($queryParameters);
            }
            $queryString = http_build_query($queryParameters);
        } else {
            $queryString = http_build_query($event['queryStringParameters'] ?? []);
            parse_str($queryString, $queryParameters);
            if (! empty($queryString)) {
                $uri .= '?' . $queryString;
            }
        }

         $protocol = $event['requestContext']['protocol'] ?? 'HTTP/1.1';
         $path = $event['path'] ?? '/';
         $request->setRequestUri($uri);
         $request->setRemoteAddress('127.0.0.1');
         $request->setServerAddress('127.0.0.1');
         $request->setServerProtocol($protocol);
         $request->setCustomVar('PATH_INFO', $path);
         $request->setCustomVar('QUERY_STRING', $queryString);
         $request->setRemotePort(80);
         $request->setServerName('localhost');
         $request->setServerPort(80);
        if (array_key_exists('multiValueHeaders', $event)) {
            $headers = $event['multiValueHeaders'];
            $headers = array_change_key_case($headers, CASE_LOWER);
            $port = $headers['x-forwarded-port'][0] ?? 80;
            $request->setRemotePort((int) $port);
            $request->setServerPort((int) $port);
            $request->setServerName($headers['host'][0] ?? 'localhost');

            if (($method === 'POST') && ! isset($headers['content-type'])) {
                $headers['content-type'] = ['application/x-www-form-urlencoded'];
            }
            if (isset($headers['content-type'])) {
                $request->setContentType($headers['content-type'][0]);
            }
            if (($method === 'POST') && ! isset($headers['content-length'])) {
                $headers['content-length'] = [strlen($requestBody)];
            }
            foreach ($headers as $name => $values) {
                foreach ($values as $value) {
                    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
                    $request->setCustomVar($key, $value);
                }
            }
        } else {
                  // See https://stackoverflow.com/a/5519834/245552
            if (! empty($requestBody) && $method !== 'TRACE' && ! isset($headers['content-type'])) {
                $headers['content-type'] = 'application/x-www-form-urlencoded';
            }
            if (isset($headers['content-type'])) {
                $request->setContentType($headers['content-type']);
            }
                   // Auto-add the Content-Length header if it wasn't provided
                   // See https://github.com/mnapoli/bref/issues/162
            if (! empty($requestBody) && $method !== 'TRACE' && ! isset($headers['content-length'])) {
                $headers['content-length'] = strlen($requestBody);
            }

            foreach ($headers as $header => $value) {
                $key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
                $request->setCustomVar($key, $value);
            }
        }
         return $request;
    }

    /**
     * This methods makes sure to kill any existing PHP-FPM process.
     */
    private function killExistingFpm(): void
    {
        // Never seen this happen but just in case
        if (! file_exists(self::PID_FILE)) {
            unlink(self::SOCKET);
            return;
        }

        $pid = (int) file_get_contents(self::PID_FILE);

        // Never seen this happen but just in case
        if ($pid <= 0) {
            echo "PHP-FPM's PID file contained an invalid PID, assuming PHP-FPM isn't running.\n";
            unlink(self::SOCKET);
            unlink(self::PID_FILE);
            return;
        }

        // Check if the process is running
        if (posix_getpgid($pid) === false) {
            // PHP-FPM is not running anymore, we can cleanup
            unlink(self::SOCKET);
            unlink(self::PID_FILE);
            return;
        }

        echo "PHP-FPM seems to be running already, this might be because Lambda stopped the bootstrap process but didn't leave us an opportunity to stop PHP-FPM. Stopping PHP-FPM now to restart from a blank slate.\n";

        // PHP-FPM is running, let's try to kill it properly
        $result = posix_kill($pid, SIGTERM);
        if ($result === false) {
            echo "PHP-FPM's PID file contained a PID that doesn't exist, assuming PHP-FPM isn't running.\n";
            unlink(self::SOCKET);
            unlink(self::PID_FILE);
            return;
        }

        $this->waitUntilStopped($pid);
        unlink(self::SOCKET);
        unlink(self::PID_FILE);
    }

    /**
     * Wait until PHP-FPM has stopped.
     */
    private function waitUntilStopped(int $pid): void
    {
        $wait = 5000; // 5ms
        $timeout = 1000000; // 1 sec
        $elapsed = 0;
        while (posix_getpgid($pid) !== false) {
            usleep($wait);
            $elapsed += $wait;
            if ($elapsed > $timeout) {
                throw new \Exception('Timeout while waiting for PHP-FPM to stop');
            }
        }
    }
}
