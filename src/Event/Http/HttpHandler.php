<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\LambdaResult;
use LogicException;

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
     * Implement it to return the response to the request, or override respond() to write it as it is produced.
     */
    public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse
    {
        throw new LogicException(static::class . ' must implement handleRequest() or respond()');
    }

    /** {@inheritDoc} */
    public function handle($event, Context $context): array|LambdaResult
    {
        // See https://bref.sh/docs/runtimes/http.html#cold-starts
        if (isset($event['warmer']) && $event['warmer'] === true) {
            // Delay the response to ensure concurrent invocation
            // See https://github.com/brefphp/bref/pull/734
            usleep(10000); // 10ms
            return ['Lambda is warm'];
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
        $respond = $fromCdn
            ? $this->respond(...)
            : static fn (HttpRequestEvent $event, Context $context, HttpResponseWriter $writer) => (new HttpResponse('Forbidden', ['Content-Type' => 'text/plain'], 403))->sendTo($writer);

        $httpEvent = new HttpRequestEvent($event);
        // Streamed Function URLs and REST APIs send the response to the client as it is written
        $streamed = HttpRequestEvent::isStreamed($event);

        return new LambdaResult(
            $streamed ? HttpResponseWriter::STREAMED_CONTENT_TYPE : 'application/json',
            function (callable $send, callable $end) use ($httpEvent, $context, $streamed, $respond): void {
                $writer = $streamed
                    ? HttpResponseWriter::streamed($send, $end, $context->getAwsRequestId())
                    : HttpResponseWriter::buffered($httpEvent, $send, $end, $context->getAwsRequestId());
                $respond($httpEvent, $context, $writer);
                $writer->end();
            },
        );
    }

    /**
     * Write the response to the request: by default the one returned by handleRequest().
     *
     * Override it to write the response as it is produced, e.g. the output of a framework (see HttpResponseWriter).
     */
    protected function respond(HttpRequestEvent $event, Context $context, HttpResponseWriter $writer): void
    {
        $this->handleRequest($event, $context)->sendTo($writer);
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
