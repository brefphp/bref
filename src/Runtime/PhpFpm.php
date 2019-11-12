<?php declare(strict_types=1);

namespace Bref\Runtime;

use Bref\Http\LambdaResponse;
use Bref\Runtime\FastCgi\FastCgiCommunicationFailed;
use Bref\Runtime\FastCgi\FastCgiRequest;
use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Interfaces\ProvidesRequestData;
use hollodotme\FastCGI\Interfaces\ProvidesResponseData;
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
 *
 * @internal
 */
final class PhpFpm
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
     * Proxy the API Gateway event to PHP-FPM and return its response.
     *
     * @param mixed $event
     */
    public function proxy($event): LambdaResponse
    {
        if (isset($event['warmer']) && $event['warmer'] === true) {
            return new LambdaResponse(100, [], 'Lambda is warm');
        }

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

        $isMultiHeader = isset($event['multiValueHeaders']);
        $responseHeaders = $this->getResponseHeaders($response, $isMultiHeader);

        // Extract the status code
        if (isset($responseHeaders['status'])) {
            $status = (int) (is_array($responseHeaders['status']) ? $responseHeaders['status'][0]: $responseHeaders['status']);
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

        $queryString = $this->getQueryString($event);
        $uri = $event['path'] ?? '/';
        if (! empty($queryString)) {
            $uri .= '?' . $queryString;
        }

        $protocol = $event['requestContext']['protocol'] ?? 'HTTP/1.1';

        // Normalize headers
        if (isset($event['multiValueHeaders'])) {
            $headers = $event['multiValueHeaders'];
        } else {
            $headers = $event['headers'] ?? [];
            // Turn the headers array into a multi-value array to simplify the code below
            $headers = array_map(function ($value): array {
                return [$value];
            }, $headers);
        }
        $headers = array_change_key_case($headers, CASE_LOWER);

        $request->setRequestUri($uri);
        $request->setRemoteAddress('127.0.0.1');
        $request->setRemotePort((int) ($headers['x-forwarded-port'][0] ?? 80));
        $request->setServerAddress('127.0.0.1');
        $request->setServerName($headers['host'][0] ?? 'localhost');
        $request->setServerProtocol($protocol);
        $request->setServerPort((int) ($headers['x-forwarded-port'][0] ?? 80));
        $request->setCustomVar('PATH_INFO', $event['path'] ?? '/');
        $request->setCustomVar('QUERY_STRING', $queryString);
        if ($event['requestContext'] ?? false) {
            $this->setArrayValue('REQUEST_CONTEXT', $event['requestContext'], $request);
        }
        // See https://stackoverflow.com/a/5519834/245552
        if (! empty($requestBody) && $method !== 'TRACE' && ! isset($headers['content-type'])) {
            $headers['content-type'] = ['application/x-www-form-urlencoded'];
        }
        if (isset($headers['content-type'][0])) {
            $request->setContentType($headers['content-type'][0]);
        }
        // Auto-add the Content-Length header if it wasn't provided
        // See https://github.com/brefphp/bref/issues/162
        if (! empty($requestBody) && $method !== 'TRACE' && ! isset($headers['content-length'])) {
            $headers['content-length'] = [strlen($requestBody)];
        }
        foreach ($headers as $header => $values) {
            foreach ($values as $value) {
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

    private function getQueryString(array $event): string
    {
        if (isset($event['multiValueQueryStringParameters']) && $event['multiValueQueryStringParameters']) {
            $queryParameters = [];
            /*
             * Watch out: to support multiple query string parameters with the same name like:
             *     ?array[]=val1&array[]=val2
             * we need to support "multi-value query string", else only the 'val2' value will survive.
             * At the moment we only take the first value (which means we DON'T support multiple values),
             * this needs to be implemented below in the future.
             */
            foreach ($event['multiValueQueryStringParameters'] as $key => $value) {
                $queryParameters[$key] = $value[0];
            }
            return http_build_query($queryParameters);
        }

        if (empty($event['queryStringParameters'])) {
            return '';
        }

        /*
         * Watch out in the future if using $event['queryStringParameters'] directly!
         *
         * (that is no longer the case here but it was in the past with the PSR-7 bridge, and it might be
         * reintroduced in the future)
         *
         * queryStringParameters does not handle correctly arrays in parameters
         * ?array[key]=value gives ['array[key]' => 'value'] while we want ['array' => ['key' = > 'value']]
         * In that case we should recreate the original query string and use parse_str which handles correctly arrays
         */
        return http_build_query($event['queryStringParameters']);
    }

    /**
     * Return an array of the response headers.
     */
    private function getResponseHeaders(ProvidesResponseData $response, bool $isMultiHeader): array
    {
        // TODO this might need some changes when upgrading the hollodotme library
        // See https://github.com/hollodotme/fast-cgi-client/blob/master/CHANGELOG.md#300-alpha---2019-04-30
        if ($isMultiHeader) {
            $responseHeaders = [];
            $lines  = explode(PHP_EOL, $response->getOutput());
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
     * Recursively loop through a data object and create env variables
     */
    private function setArrayValue(string $name, array $array, FastCgiRequest $request): void
    {
        if (! is_array($array)) {
            $request->setCustomVar(strtoupper($name), $array);
        }

        foreach ($array as $key => $value) {
            if (! is_array($value)) {
                $request->setCustomVar(strtoupper($name . '_' . $key), $value);
            }

            if (is_array($value)) {
                $this->setArrayValue(strtoupper($name . '_' . $key), $value, $request);
            }
        }
    }
}
