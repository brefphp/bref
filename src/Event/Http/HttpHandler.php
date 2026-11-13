<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\LambdaResult;
use LogicException;

abstract class HttpHandler implements Handler
{
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

        $httpEvent = new HttpRequestEvent($event);
        // Streamed Function URLs and REST APIs send the response to the client as it is written
        $streamed = HttpRequestEvent::isStreamed($event);

        return new LambdaResult(
            $streamed ? HttpResponseWriter::STREAMED_CONTENT_TYPE : 'application/json',
            function (callable $send, callable $end) use ($httpEvent, $context, $streamed): void {
                $writer = $streamed
                    ? HttpResponseWriter::streamed($send, $end, $context->getAwsRequestId())
                    : HttpResponseWriter::buffered($httpEvent, $send, $end, $context->getAwsRequestId());
                $this->respond($httpEvent, $context, $writer);
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
}
