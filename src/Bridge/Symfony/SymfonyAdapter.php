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

        if (!is_null($symfonyRequest->cookies->get(session_name()))) {
            $this->httpKernel->getContainer()->get('session')->setId(
                $symfonyRequest->cookies->get(session_name())
            );
        }

        $symfonyResponse = $this->httpKernel->handle($symfonyRequest);

        $symfonyResponse->headers->setCookie(
            new Cookie(
                session_name(),
                $this->httpKernel->getContainer()->get('session')->getId()
            )
        );

        if ($this->httpKernel instanceof TerminableInterface) {
            $this->httpKernel->terminate($symfonyRequest, $symfonyResponse);
        }

        $psr7Factory = new DiactorosFactory;
        $response = $psr7Factory->createResponse($symfonyResponse);

        return $response;
    }
}
