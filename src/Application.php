<?php
declare(strict_types=1);

namespace PhpLambda;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class Application
{
    public function run(callable $handler)
    {
        $handler([]);
    }
}
