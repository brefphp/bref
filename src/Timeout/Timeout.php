<?php declare(strict_types=1);

namespace Bref\Timeout;

/**
 * Helper class to trigger an exception just before the Lamba times out. This
 * will give the application a chance to shut down.
 */
final class Timeout
{
    /** @var bool */
    private static $initialized = false;

    /**
     * Read environment variables and setup timeout exception.
     */
    public static function enable(): void
    {
        if (isset($_SERVER['BREF_TIMEOUT'])) {
            $timeout = (int) $_SERVER['BREF_TIMEOUT'];
            if ($timeout === -1) {
                return;
            }

            if ($timeout > 0) {
                self::timeoutAfter($timeout);

                return;
            }

            // else if 0, continue
        }

        if (isset($_SERVER['LAMBDA_INVOCATION_CONTEXT'])) {
            $context = json_decode($_SERVER['LAMBDA_INVOCATION_CONTEXT'], true, 512, JSON_THROW_ON_ERROR);
            $deadlineMs = $context['deadlineMs'];
            $remainingTime = $deadlineMs - intval(microtime(true) * 1000);

            self::timeoutAfter((int) floor($remainingTime / 1000));

            return;
        }

        throw new \LogicException('Could not find value for bref timeout. Are we running on Lambda?');
    }

    /**
     * Setup custom handler for SIGTERM. One need to call Timeout::timoutAfter()
     * to make an exception to be thrown.
     *
     * @return bool true if successful.
     */
    public static function init(): bool
    {
        if (self::$initialized) {
            return true;
        }

        if (! function_exists('pcntl_async_signals')) {
            trigger_error('Could not enable timeout exceptions because pcntl extension is not enabled.');
            return false;
        }

        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, function (): void {
            throw new LambdaTimeout('Maximum AWS Lambda execution time reached');
        });

        self::$initialized = true;

        return true;
    }

    /**
     * Set a timer to throw an exception.
     */
    public static function timeoutAfter(int $seconds): void
    {
        self::init();
        pcntl_alarm($seconds);
    }

    /**
     * Reset timeout.
     */
    public static function reset(): void
    {
        if (self::$initialized) {
            pcntl_alarm(0);
        }
    }
}
