<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\InMemoryResponseStream;

abstract class HttpHandler implements Handler
{
    /**
     * The header that the CDN in front of the function (e.g. CloudFront) adds to the requests it sends,
     * with the value of the `BREF_ORIGIN_SECRET` environment variable.
     */
    public const ORIGIN_SECRET_HEADER = 'x-bref-origin-secret';

    /**
     * The header in which the CDN sends the query string the client sent, when it had to encode some of its
     * characters: Function URLs reject e.g. the unencoded brackets of `?filter[status]=active`.
     */
    public const RAW_QUERY_STRING_HEADER = 'x-bref-raw-query-string';

    /**
     * Write the response to the request: its status and headers, then its body as it is produced.
     *
     * Call `$responseWriter->end()` before the work that the client must not wait for (e.g. terminate
     * callbacks): the response ends by itself otherwise.
     */
    abstract public function handleRequest(HttpRequestEvent $event, Context $context, HttpResponseWriter $responseWriter): void;

    /**
     * The response to the event, in the buffered format of its invoker (API Gateway, ALB…).
     *
     * Not used by the runtime, which calls writeResponse(): it is for the code that calls the handler
     * directly (tests, `bref-local`).
     *
     * Do not return it from another handler on Lambda (e.g. a function that wraps this handler): the
     * runtime only streams the response of the HTTP handler it is given. A streamed Function URL would
     * show this array as the body of the response.
     */
    public function handle(mixed $event, Context $context): array
    {
        $lambdaResponse = new InMemoryResponseStream;
        $responseWriter = new HttpResponseWriter($lambdaResponse, $event, streamed: false, awsRequestId: $context->getAwsRequestId());

        $this->writeResponse($event, $context, $responseWriter);
        $responseWriter->end();

        return json_decode($lambdaResponse->contents(), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Called by the runtime, with the writer of the HTTP response: the runtime sends what is written,
     * streamed or buffered depending on what invoked the function.
     *
     * @internal Implement handleRequest() to write the response, call handle() to get it.
     */
    public function writeResponse(mixed $event, Context $context, HttpResponseWriter $responseWriter): void
    {
        // See https://bref.sh/docs/runtimes/http.html#cold-starts
        if (isset($event['warmer']) && $event['warmer'] === true) {
            // Delay the response to ensure concurrent invocation
            // See https://github.com/brefphp/bref/pull/734
            usleep(10000); // 10ms
            $responseWriter->respond('Lambda is warm', ['Content-Type' => 'text/plain']);
            return;
        }

        // With `BREF_ORIGIN_SECRET`, only the requests that went through the CDN are answered: calling
        // the Function URL directly would bypass it (cache, firewall, custom domain…)
        $originSecret = getenv('BREF_ORIGIN_SECRET');
        $hasOriginSecret = is_string($originSecret) && $originSecret !== '';
        $receivedSecret = is_array($event) ? self::takeHeader($event, self::ORIGIN_SECRET_HEADER) : '';
        $fromCdn = ! $hasOriginSecret || hash_equals($originSecret, $receivedSecret);
        // Trusted only with the origin secret: anyone could send this header to the function otherwise
        $rawQueryString = is_array($event) ? self::takeHeader($event, self::RAW_QUERY_STRING_HEADER) : '';
        if ($hasOriginSecret && $fromCdn && $rawQueryString !== '' && ($event['version'] ?? null) === '2.0') {
            $event['rawQueryString'] = $rawQueryString;
        }

        $httpEvent = new HttpRequestEvent($event);
        if (! $fromCdn) {
            $responseWriter->respond('Forbidden', ['Content-Type' => 'text/plain'], 403);
            return;
        }

        $this->handleRequest($httpEvent, $context, $responseWriter);
    }

    /**
     * Removes a header meant for Bref, so that the application never sees it (or logs it).
     *
     * @return string Its value, or an empty string without the header.
     */
    private static function takeHeader(array &$event, string $header): string
    {
        $found = '';
        foreach (['headers', 'multiValueHeaders'] as $key) {
            if (! isset($event[$key]) || ! is_array($event[$key])) {
                continue;
            }
            foreach ($event[$key] as $name => $value) {
                if (strtolower((string) $name) !== $header) {
                    continue;
                }
                if (is_array($value)) {
                    $value = $value[0] ?? '';
                }
                $found = is_string($value) ? $value : $found;
                unset($event[$key][$name]);
            }
        }
        return $found;
    }
}
