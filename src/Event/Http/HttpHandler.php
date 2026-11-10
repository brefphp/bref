<?php declare(strict_types=1);

namespace Bref\Event\Http;

use Bref\Context\Context;
use Bref\Event\Handler;
use Bref\Runtime\StreamedResponse;
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

        if (HttpRequestEvent::isFunctionUrlEvent($event)) {
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
     * Function URLs (in `RESPONSE_STREAM` mode) send the response to the client as it is produced.
     *
     * Lambda does not turn errors into a 502 response for streamed responses: the 500 is sent here
     * (otherwise the error, stack trace included, would be the body of a 200 response).
     */
    private function stream(array $event, Context $context): StreamedResponse
    {
        return new StreamedResponse(
            'application/vnd.awslambda.http-integration-response',
            function (callable $write) use ($event, $context): void {
                try {
                    $response = $this->handleRequest(new HttpRequestEvent($event), $context);
                } catch (Throwable $e) {
                    (new HttpResponse('Internal Server Error', ['content-type' => 'text/plain'], 500))->toStreamedResponse()->writeTo($write);

                    throw $e;
                }
                $response->toStreamedResponse($context->getAwsRequestId())->writeTo($write);
            },
        );
    }
}
