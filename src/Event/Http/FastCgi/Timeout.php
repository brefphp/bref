<?php declare(strict_types=1);

namespace Bref\Event\Http\FastCgi;

/**
 * There was a timeout while processing the PHP request
 *
 * @internal
 */
final class Timeout extends \Exception
{
    public function __construct(int $taskTimeoutInMs, string $requestId)
    {
        $message = "The request $requestId timed out after $taskTimeoutInMs ms. "
            . 'Note: that duration may be lower than the Lambda timeout, don\'t be surprised, that is intentional. '
            . 'Indeed, Bref stops the PHP-FPM request *before* a hard Lambda timeout, because a hard timeout prevents all logs to be written to CloudWatch.';

        parent::__construct($message);
    }
}
