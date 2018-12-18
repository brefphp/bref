<?php declare(strict_types=1);

namespace Bref\Runtime;

use Bref\Http\LambdaResponse;
use hollodotme\FastCGI\Client;
use hollodotme\FastCGI\Requests\AbstractRequest;
use hollodotme\FastCGI\SocketConnections\UnixDomainSocket;
use Symfony\Component\Process\Process;

/**
 * Proxies HTTP events coming from API Gateway to PHP-FPM via FastCGI.
 */
class PhpFpm
{
    private const SOCKET = '/tmp/.bref/php-fpm.sock';
    private const CONFIG = '/opt/php/fpm/php-fpm.conf';

    /** @var Client */
    private $client;
    /** @var string */
    private $handler;
    /** @var string */
    private $configFile;
    /** @var Process|null */
    private $fpm;

    public function __construct(string $handler, string $configFile = self::CONFIG)
    {
        $this->client = new Client(new UnixDomainSocket(self::SOCKET, 5000, 30000));
        $this->handler = $handler;
        $this->configFile = $configFile;
    }

    public function start(): void
    {
        if (! is_dir(dirname(self::SOCKET))) {
            mkdir(dirname(self::SOCKET));
        }

        $this->fpm = new Process(['php-fpm', '--nodaemonize', '--fpm-config', $this->configFile]);
        $this->fpm->setTimeout(null);
        $this->fpm->start(function ($type, $output): void {
            if ($type === Process::ERR) {
                echo $output;
                exit(1);
            }
        });

        $this->waitForServerReady();
    }

    public function stop(): void
    {
        if ($this->fpm && $this->fpm->isRunning()) {
            $this->fpm->stop();
        }
    }

    public function __destruct()
    {
        $this->stop();
    }

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
        if (! isset($event['httpMethod'])) {
            throw new \Exception('The lambda was not invoked via HTTP through API Gateway: this is not supported by this runtime');
        }

        $request = $this->eventToFastCgiRequest($event);

        $response = $this->client->sendRequest($request);

        $headers = $response->getHeaders();
        $headers['status'] = $headers['status'] ?? '200 Ok';

        [$status] = explode(' ', $headers['status']);

        return new LambdaResponse((int) $status, $headers, $response->getBody());
    }

    private function waitForServerReady(): void
    {
        $wait = 5000; // 5ms
        $timeout = 5000000; // 5 secs
        $elapsed = 0;

        while (! $this->isReady()) {
            usleep($wait);
            $elapsed += $wait;
            if ($elapsed > $timeout) {
                echo 'Timeout while waiting for socket at ' . self::SOCKET;
                exit(1);
            }
        }

        echo 'FastCGI started';
    }

    private function isReady(): bool
    {
        clearstatcache(false, self::SOCKET);

        return file_exists(self::SOCKET);
    }

    private function eventToFastCgiRequest(array $event): AbstractRequest
    {
        $bodyString = $event['body'] ?? '';
        if ($event['isBase64Encoded'] ?? false) {
            $bodyString = base64_decode($bodyString);
        }

        $request = new FastCgiRequest($event['httpMethod'], $this->handler, $bodyString);

        $uri = $event['path'] ?? '/';
        /*
         * queryStringParameters does not handle correctly arrays in parameters
         * ?array[key]=value gives ['array[key]' => 'value'] while we want ['array' => ['key' = > 'value']]
         * We recreate the original query string and we use parse_str which handles correctly arrays
         *
         * There's still an issue: AWS API Gateway does not support multiple query string parameters with the same name
         * So you can't use something like ?array[]=val1&array[]=val2 because only the 'val2' value will survive
         */
        $queryString = http_build_query($event['queryStringParameters'] ?? []);
        parse_str($queryString, $queryParameters);
        if (! empty($queryString)) {
            $uri .= '?' . $queryString;
        }
        $request->setRequestUri($uri);
        $request->setCustomVar('QUERY_STRING', http_build_query($queryParameters));

        $request->setRemoteAddress('127.0.0.1');
        $request->setRemotePort(80);
        $request->setServerName('127.0.0.1');
        $request->setServerPort(80);
        if (isset($event['requestContext']['protocol'])) {
            $request->setServerProtocol($event['requestContext']['protocol']);
        }

        $headers = $event['headers'] ?? [];
        if (isset($headers['Host'])) {
            $request->setServerName($headers['Host']);
        }

        if (isset($headers['Content-Type'])) {
            $request->setContentType($headers['Content-Type']);
        }

        foreach ($headers as $header => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $header));
            $request->setCustomVar($key, $value);
        }

        return $request;
    }
}
