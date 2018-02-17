<?php
declare(strict_types=1);

namespace Bref\Http;

use Interop\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * This is the default HTTP handler that shows a welcome page.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class WelcomeHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $html = file_get_contents(__DIR__ . '/welcome.html');

        return new HtmlResponse($html);
    }
}
