<?php
declare(strict_types=1);

namespace PhpLambda;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface HttpApplication
{
    public function process(array $event) : LambdaResponse;
}
