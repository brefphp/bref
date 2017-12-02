<?php
declare(strict_types=1);

namespace PhpLambda;

/**
 * Handles an HTTP request.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface HttpHandler
{
    public function handle(array $event) : LambdaResponse;
}
