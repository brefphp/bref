<?php declare(strict_types=1);

namespace Bref\Runtime;

use Exception;

/**
 * @internal
 */
class ResponseTooBig extends Exception
{
    public function __construct()
    {
        parent::__construct('The Lambda response is too big and above the limit: 6 MB for HTTP responses and other synchronous invocations, 256 KB for asynchronous invocations (https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-limits.html#function-configuration-deployment-and-execution)');
    }
}
