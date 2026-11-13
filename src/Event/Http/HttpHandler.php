<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\LambdaResult;
use LogicException;

abstract class HttpHandler implements Handler
{
    abstract public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse;

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
        if (HttpRequestEvent::isStreamed($event)) {
            return new LambdaResult(HttpResponseWriter::STREAMED_CONTENT_TYPE, function (callable $send) use ($httpEvent, $context): void {
                $writer = HttpResponseWriter::streamed($send, $context->getAwsRequestId());
                $this->respond($httpEvent, $context, $writer);
                if (! $writer->started()) {
                    throw new LogicException('The HTTP handler did not write any response');
                }
            });
        }

        $writer = HttpResponseWriter::buffered($context->getAwsRequestId());
        $this->respond($httpEvent, $context, $writer);

        return $writer->toApiGatewayFormat($httpEvent);
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
