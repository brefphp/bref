<?php declare(strict_types=1);

namespace Bref\Runtime;

use Exception;

/**
 * The response could not be sent before the Lambda timeout: Bref ended it shortly before, with this error.
 *
 * @internal
 */
class ResponseTimedOut extends Exception
{
    public function __construct()
    {
        parent::__construct('The response could not be sent before the Lambda timeout, Bref ended it a few seconds before so that this Lambda instance keeps serving requests. Lambda throttles big streamed responses after their first megabytes.');
    }
}
