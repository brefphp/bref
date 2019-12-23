<?php declare(strict_types=1);

namespace Bref\Handler;

use Bref\Context\Context;
use Bref\Event\Http\HttpRequestEvent;
use Bref\Http\HttpResponse;
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
 *     $lambdaResponse = $phpFpm->handle($event);
 *     $phpFpm->stop();
 *     [send the $lambdaResponse];
 *
 * @internal
 */
final class FpmHandler implements Handler
{
    private const SOCKET = '/tmp/.bref/php-fpm.sock';
    private const PID_FILE = '/tmp/.bref/php-fpm.pid';
    private const CONFIG = '/opt/bref/etc/php-fpm.conf';

    /** @var Client|null */
    private $client;
    /** @var UnixDomainSocket */
    private $connection;
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

        $this->client = new Client;
        $this->connection = new UnixDomainSocket(self::SOCKET, 1000, 30000);

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
    public function handle($event, Context $context): array
    {
        if (isset($event['warmer']) && $event['warmer'] === true) {
            return ['Lambda is warm'];
        }

        $httpEvent = new HttpRequestEvent($event);
        $request = $this->eventToFastCgiRequest($httpEvent);

        try {
            $response = $this->client->sendRequest($this->connection, $request);
        } catch (\Throwable $e) {
            throw new FastCgiCommunicationFailed(sprintf(
                'Error communicating with PHP-FPM to read the HTTP response. A root cause of this can be that the Lambda (or PHP) timed out, for example when trying to connect to a remote API or database, if this happens continuously check for those! Original exception message: %s %s',
                get_class($e),
                $e->getMessage()
            ), 0, $e);
        }

        $responseHeaders = $this->getResponseHeaders($response, $httpEvent->hasMultiHeader());

        // Extract the status code
        if (isset($responseHeaders['status'])) {
            $status = (int) (is_array($responseHeaders['status']) ? $responseHeaders['status'][0]: $responseHeaders['status']);
            unset($responseHeaders['status']);
        }

        $response = new HttpResponse($status ?? 200, $responseHeaders, $response->getBody());

        return $response->toApiGatewayFormat($httpEvent->hasMultiHeader());
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

    private function eventToFastCgiRequest(HttpRequestEvent $event): ProvidesRequestData
    {
        $request = new FastCgiRequest($event->getMethod(), $this->handler, $event->getBody());
        $request->setRequestUri($event->getUri());
        $request->setRemoteAddress('127.0.0.1');
        $request->setRemotePort($event->getRemotePort());
        $request->setServerAddress('127.0.0.1');
        $request->setServerName($event->getServerName());
        $request->setServerProtocol($event->getProtocol());
        $request->setServerPort($event->getServerPort());
        $request->setCustomVar('PATH_INFO', $event->getPath());
        $request->setCustomVar('QUERY_STRING', $event->getQueryString());
        $contentType = $event->getContentType();
        if ($contentType) {
            $request->setContentType($contentType);
        }
        foreach ($event->getHeaders() as $header => $values) {
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

    /**
     * Return an array of the response headers.
     */
    private function getResponseHeaders(ProvidesResponseData $response, bool $isMultiHeader): array
    {
        $responseHeaders = $response->getHeaders();
        if (! $isMultiHeader) {
            // If we are not in "multi-header" mode, we must keep the last value only
            // and cast it to string
            foreach ($responseHeaders as $key => $value) {
                $responseHeaders[$key] = end($value);
            }
        }

        return array_change_key_case($responseHeaders, CASE_LOWER);
    }
}
