<?php declare(strict_types=1);

namespace Bref\Bridge\Symfony;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

/**
 * Adapter for using the Symfony framework as a HTTP handler.
 */
class SymfonyAdapter implements RequestHandlerInterface
{
    /** @var HttpKernelInterface */
    private $httpKernel;

    public function __construct(HttpKernelInterface $httpKernel)
    {
        $this->httpKernel = $httpKernel;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $symfonyRequest = (new HttpFoundationFactory)->createRequest($request);

        $requestSessionId = $this->loadSessionFromRequest($symfonyRequest);

        $symfonyResponse = $this->httpKernel->handle($symfonyRequest);

        $this->addSessionCookieToResponseIfChanged($requestSessionId, $symfonyResponse);

        if ($this->httpKernel instanceof TerminableInterface) {
            $this->httpKernel->terminate($symfonyRequest, $symfonyResponse);
        }

        return (new DiactorosFactory)->createResponse($symfonyResponse);
    }

    private function loadSessionFromRequest(Request $symfonyRequest): string
    {
        if ($this->hasSessionsDisabled()) {
            return '';
        }

        $sessionId = $symfonyRequest->cookies->get(session_name(), '');
        $this->httpKernel->getContainer()->get('session')->setId($sessionId);

        return $sessionId;
    }

    private function addSessionCookieToResponseIfChanged(?string $requestSessionId, Response $symfonyResponse): void
    {
        if ($this->hasSessionsDisabled()) {
            return;
        }

        $responseSessionId = $this->httpKernel->getContainer()->get('session')->getId();

        if ($requestSessionId === $responseSessionId) {
            return;
        }

        $cookie = session_get_cookie_params();

        $symfonyResponse->headers->setCookie(
            new Cookie(
                session_name(),
                $responseSessionId,
                $cookie['lifetime'],
                $cookie['path'],
                $cookie['domain'],
                $cookie['secure'],
                $cookie['httponly'],
                false,
                $cookie['samesite'] ?? null
            )
        );
    }

    private function hasSessionsDisabled(): bool
    {
        return $this->httpKernel->getContainer()->has('session') === false;
    }
}
