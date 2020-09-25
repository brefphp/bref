<?php declare(strict_types=1);

namespace Bref\Runtime;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class HandlerNotFound extends Exception implements NotFoundExceptionInterface
{
}
