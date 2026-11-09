<?php declare(strict_types=1);

namespace Bref\FpmRuntime\FastCgi;

use RuntimeException;

/**
 * PHP-FPM did not answer before the deadline.
 *
 * @internal
 */
final class TimedOut extends RuntimeException
{
}
