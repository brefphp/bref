<?php declare(strict_types=1);

namespace Bref\FpmRuntime\FastCgi;

use Exception;

/**
 * Thrown while PHP-FPM sends its response, to stop reading it: the rest is not wanted.
 *
 * @internal
 */
final class StopReadingResponse extends Exception
{
}
