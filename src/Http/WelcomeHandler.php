<?php declare(strict_types=1);

namespace Bref\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * This is the default HTTP handler that shows a welcome page.
 */
class WelcomeHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $html = file_get_contents(__DIR__ . '/welcome.html');
        if ($html === false) {
            throw new \RuntimeException('Unable to read the `welcome.html` template');
        }

        return new HtmlResponse($html);
    }
}
