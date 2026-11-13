<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\StreamedResponse;

abstract class HttpHandler implements Handler
{
    abstract public function handleRequest(HttpRequestEvent $event, Context $context): HttpResponse;

    /** {@inheritDoc} */
    public function handle($event, Context $context): array|StreamedResponse
    {
        // See https://bref.sh/docs/runtimes/http.html#cold-starts
        if (isset($event['warmer']) && $event['warmer'] === true) {
            // Delay the response to ensure concurrent invocation
            // See https://github.com/brefphp/bref/pull/734
            usleep(10000); // 10ms
            return ['Lambda is warm'];
        }

        if (HttpRequestEvent::isStreamed($event)) {
            return $this->stream($event, $context);
        }

        $httpEvent = new HttpRequestEvent($event);

        $response = $this->handleRequest($httpEvent, $context);

        if ($httpEvent->isFormatV2()) {
            return $response->toApiGatewayFormatV2($context->getAwsRequestId());
        }

        return $response->toApiGatewayFormat($httpEvent->hasMultiHeader(), $context->getAwsRequestId());
    }

    /**
     * Streamed Function URLs and REST APIs send the response to the client as it is produced.
     */
    private function stream(array $event, Context $context): StreamedResponse
    {
        return $this->handleRequest(new HttpRequestEvent($event), $context)->toStreamedResponse($context->getAwsRequestId());
    }
}
