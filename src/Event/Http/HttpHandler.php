<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\StreamedResponse;
use Generator;
use Throwable;

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

        $httpEvent = new HttpRequestEvent($event);

        if ($httpEvent->isFunctionUrl()) {
            return $this->stream($httpEvent, $context);
        }

        $response = $this->handleRequest($httpEvent, $context);

        if ($httpEvent->isFormatV2()) {
            return $response->toApiGatewayFormatV2($context->getAwsRequestId());
        }

        return $response->toApiGatewayFormat($httpEvent->hasMultiHeader(), $context->getAwsRequestId());
    }

    /**
     * Function URLs (in `RESPONSE_STREAM` mode) send the response to the client as it is produced.
     *
     * Lambda no longer turns errors into a 502 response for streamed responses: the 500 is sent here.
     */
    private function stream(HttpRequestEvent $event, Context $context): StreamedResponse
    {
        $chunks = function () use ($event, $context): Generator {
            try {
                $response = $this->handleRequest($event, $context);
            } catch (Throwable $e) {
                yield from (new HttpResponse('Internal Server Error', ['content-type' => 'text/plain'], 500))->toStreamedResponse()->chunks;

                throw $e;
            }
            yield from $response->toStreamedResponse($context->getAwsRequestId())->chunks;
        };

        return new StreamedResponse('application/vnd.awslambda.http-integration-response', $chunks());
    }
}
