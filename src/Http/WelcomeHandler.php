<?php
declare(strict_types=1);

namespace PhpLambda\Http;

use PhpLambda\HttpHandler;
use PhpLambda\LambdaResponse;

/**
 * This is the default HTTP handler that shows a welcome page.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class WelcomeHandler implements HttpHandler
{
    public function handle(array $event) : LambdaResponse
    {
        $html = file_get_contents(__DIR__ . '/welcome.html');

        return LambdaResponse::fromHtml($html);
    }
}
