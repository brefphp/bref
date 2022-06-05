<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Exception;
use GuzzleHttp\Client;
use Bref\Context\Context;
use Bref\Event\Http\FastCgi\Timeout;
use Symfony\Component\Process\Process;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;

/**
 * Handles HTTP events coming from API Gateway/ALB by proxying them to Octane via GuzzleClient.
 *
 * Usage example:
 *
 *     $event = [get the Lambda event];
 *     $octaneHandler = new OctaneHandler('swoole');
 *     $octaneHandler->start();
 *     $lambdaResponse = $octaneHandler->handle($event);
 *     $octaneHandler->stop();
 *     [send the $lambdaResponse];
 *
 * @internal
 */
final class OctaneHandler extends HttpHandler
{
    /**
     * We define this constant instead of using the PHP one because that avoids
     * depending on the pcntl extension.
     */
    private const SIGTERM = 15;

    private const STATE_FILE = '/tmp/octane/octane-server-state.json';

    protected const OCTANE_PORT = 8000;

    /** @var Client|null */
    private $client;

    /** @var string Either swoole or roadrunner driver */
    private $octaneServerDriver;

    /** @var array Custom config when starting the octane server */
    private $octaneServerConfig = [];

    /** @var Process|null */
    private $octance;

    public function __construct($octaneServerDriver, $octaneServerConfig = [])
    {
        $this->octaneServerDriver = $octaneServerDriver;
        $this->octaneServerConfig = $octaneServerConfig;
    }

    /**
     * Start the Octane process.
     */
    public function start(): void
    {
        // In case Lambda stopped our process (e.g. because of a timeout) we need to make sure Octane has stopped
        // as well and restart it
        if ($this->isReady()) {
            $this->killExistingOctane();
        }

        if ($this->octaneServerDriver === 'roadrunner') {
            $defaultRRFile = getenv('LAMBDA_TASK_ROOT') . '/.rr.yaml';
            if (file_exists($defaultRRFile)) {
                copy($defaultRRFile, '/tmp/octane/.rr.yaml');
            } else {
                if (!is_dir('/tmp/octane')) {
                    mkdir('/tmp/octane', 0755, true);
                }
                touch('/tmp/octane/.rr.yaml');
            }
            # Use /tmp/octane/.rr.yaml instead, since when starting this file will be touched
            $this->octaneServerConfig = array_merge($this->octaneServerConfig, ['--rr-config', '/tmp/octane/.rr.yaml']);
        }

        $processArgs = array_merge([
            'php',
            'artisan',
            'octane:start',
            "--server",
            $this->octaneServerDriver,
            "--port",
            self::OCTANE_PORT,
        ], $this->octaneServerConfig);

        $this->octance = new Process($processArgs);

        $this->octance->setTimeout(null);
        $this->octance->start(function ($type, $output): void {
            // Send any Octance log to CloudWatch
            echo $output;
        });

        # Init the Guzzle Client to forward the Event Request to Octane Server
        $this->client = new Client([
            'base_uri' => 'http://127.0.0.1:' . self::OCTANE_PORT,
            'cookie' => true,
        ]);

        $this->waitUntilReady();
    }

    public function stop(): void
    {
        if ($this->octance && $this->octance->isRunning()) {
            // Give it less than a second to stop (500ms should be plenty enough time)
            // this is for the case where the script timed out: we reserve 1 second before the end
            // of the Lambda timeout, so we must kill everything and restart Octane in 1 second.
            // Note: Symfony will first try sending SIGTERM (15) and then SIGKILL (9)
            $this->octance->stop(0.5);
            if ($this->isReady()) {
                throw new Exception('Octane cannot be stopped');
            }
        }
    }

    public function __destruct()
    {
        $this->stop();
    }

    private function waitUntilReady(): void
    {
        $wait = 5000; // 5ms
        $timeout = 5000000; // 5 secs
        $elapsed = 0;

        while (!$this->isReady()) {
            usleep($wait);
            $elapsed += $wait;

            if ($elapsed > $timeout) {
                throw new Exception('Timeout while waiting for Octane');
            }

            // If the process has crashed we can stop immediately
            if (!$this->octance->isRunning()) {
                throw new Exception('Octane failed to start: ' . PHP_EOL . $this->octance->getOutput() . PHP_EOL . $this->octance->getErrorOutput());
            }
        }
    }

    /**
     * @return array
     */
    private function readStateFile()
    {
        $state = is_readable(self::STATE_FILE) ? json_decode(file_get_contents(self::STATE_FILE), true) : [];

        return [
            'masterProcessId' => $state['masterProcessId'] ?? null,
            'state' => $state['state'] ?? [],
        ];
    }

    /**
     * @return bool
     */
    private function isReady(): bool
    {
        # Try to connect to octane server port (5s timeout)
        if (!$x = @fsockopen('127.0.0.1', self::OCTANE_PORT, $errno, $errstr, 5)) {
            return false;
        } else {
            @fclose($x);
            return true;
        }
    }

    /**
     * This methods makes sure to kill any existing Octane process.
     */
    private function killExistingOctane(): void
    {
        [$masterProcessId] = $this->readStateFile();

        if (is_null($masterProcessId)) {
            return;
        }

        echo "Octane seems to be running already. This might be because Lambda stopped the bootstrap process but didn't leave us an opportunity to stop Octane (did Lambda timeout?). Stopping Octane now to restart from a blank slate.\n";

        // The previous Octane process is running, let's try to kill it properly
        $result = posix_kill($masterProcessId, self::SIGTERM);
        if ($result === false) {
            echo "Octane Master PID doesn't exist, assuming Octane isn't running.\n";
            unlink(self::STATE_FILE);
            return;
        }

        $this->waitUntilStopped($masterProcessId);
    }

    /**
     * Wait until Octane has stopped.
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
                throw new Exception('Timeout while waiting for Octane to stop');
            }
        }
    }

    /**
     * Proxy the API Gateway event to Octane and return its response.
     */
    public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
    {
        // The script will timeout 1 second before the remaining time
        // to allow some time for Bref/Octane to recover and cleanup
        $margin = 1000;
        $timeoutDelayInMs = max(1000, $context->getRemainingTimeInMillis() - $margin);
        $timeoutDelayInSeconds = $timeoutDelayInMs / 1000;

        # Forward request to Octane Server
        try {
            $data = [
                'headers' => array_merge($event->getHeaders(), [
                    'PATH_INFO' => $event->getPath(),
                    'QUERY_STRING' => $event->getQueryString(),
                    'LAMBDA_INVOCATION_CONTEXT' => json_encode($context),
                    'LAMBDA_REQUEST_CONTEXT' => json_encode($event->getRequestContext()),
                ]),
                'body' => $event->getBody(),
                'query' => $event->getQueryString(),
                'timeout' => $timeoutDelayInSeconds,
            ];
            
            $response = $this->client->request($event->getMethod(), $event->getUri(), $data);
        } catch (ConnectException $e) {
            echo "The PHP script timed out. Bref will now restart Octane to start from a clean slate and flush the PHP logs.\nTimeouts can happen for example when trying to connect to a remote API or database, if this happens continuously check for those.\nIf you are using a RDS database, read this: https://bref.sh/docs/environment/database.html#accessing-the-internet\n";

            /**
             * Restart Octane so that the blocked script is 100% terminated and that its logs are flushed to stderr.
             *
             * - "why restart Octane?": if we don't, the previous request continues to execute on the next request
             * - "why not send a SIGUSR2 signal to Octane?": that was a promising approach because SIGUSR2
             *   causes Octane to cleanly stop the Octane worker that is stuck in a timeout/waiting state.
             *   It also causes all worker logs buffered by Octane to be written to stderr (great!).
             *   This takes a bit of time (a few ms), but it's faster than rebooting Octane entirely.
             *   However, the downside is that it doesn't "kill" the previous request execution:
             *   it merely stops the execution of the line of code that is waiting (e.g. "sleep()",
             *   "file_get_contents()", ...) and continues to the next line. That's super weird!
             *   So SIGUSR2 isn't a great solution in the end.
             */
            $this->stop();
            $this->start();

            // Throw an exception so that:
            // - this is reported as a Lambda execution error ("error rate" metrics are accurate)
            // - the CloudWatch logs correctly reflect that an execution error occurred
            // - the 500 response is the same as if an exception happened in Bref
            throw new Timeout($timeoutDelayInMs, $context->getAwsRequestId());
        } catch (ClientException $exception) {
            # Capture client error (4xx) and return the response normally
            $response = $exception->getResponse();
        }

        $responseHeaders = $response->getHeaders();

        return new HttpResponse((string)$response->getBody(), $responseHeaders, $response->getStatusCode());
    }
}
