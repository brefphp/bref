<?php
declare(strict_types=1);

namespace Bref\Bridge\Symfony;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Adapter for using the Symfony framework as a HTTP handler.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class SymfonyAdapter implements RequestHandlerInterface
{
    /**
     * @var HttpKernelInterface
     */
    private $httpKernel;

    public function __construct(HttpKernelInterface $httpKernel)
    {
        $this->httpKernel = $httpKernel;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $httpFoundationFactory = new HttpFoundationFactory;

        $symfonyRequest = $httpFoundationFactory->createRequest($request);

        $this->loadSessionFromCookies($symfonyRequest);

        $symfonyResponse = $this->httpKernel->handle($symfonyRequest);

        $this->addSessionCookieToResponse($symfonyResponse);

        if ($this->httpKernel instanceof TerminableInterface) {
            $this->httpKernel->terminate($symfonyRequest, $symfonyResponse);
        }

        $psr7Factory = new DiactorosFactory;
        $response = $psr7Factory->createResponse($symfonyResponse);

        return $response;
    }

    /**
     * @param $symfonyRequest
     */
    private function loadSessionFromCookies($symfonyRequest): void
    {
        if (!is_null($symfonyRequest->cookies->get(session_name()))) {
            $this->httpKernel->getContainer()->get('session')->setId(
                $symfonyRequest->cookies->get(session_name())
            );
        }
    }

    /**
     * @param $symfonyResponse
     */
    private function addSessionCookieToResponse($symfonyResponse): void
    {
        $symfonyResponse->headers->setCookie(
            new Cookie(
                session_name(),
                $this->httpKernel->getContainer()->get('session')->getId()
            )
        );
    }
}
