<?php declare(strict_types=1);

namespace Bref\Runtime;

use Exception;

/**
 * Tracks cold starts.
 *
 * @internal
 */
class ColdStartTracker
{
    private const FILE = '/tmp/.bref-cold-start';

    private static bool $currentInvocationIsColdStart;
    private static ?float $coldStartBeginningTime = null;
    private static ?float $coldStartEndedTime = null;
    private static bool $hasFirstInvocationStarted = false;
    private static bool $wasProactiveInitialization;

    public static function init(): void
    {
        self::$coldStartBeginningTime = microtime(true);

        // We need to use a file to track cold starts only once.
        // This is because Bref's process can restart between invocations,
        // so we can't rely on static variables to track cold starts.
        self::$currentInvocationIsColdStart = ! file_exists(self::FILE);
        if (self::$currentInvocationIsColdStart) {
            touch(self::FILE);
        }
    }

    /**
     * Signals that the cold start has finished.
     */
    public static function coldStartFinished(): void
    {
        self::$coldStartEndedTime = microtime(true);
    }

    /**
     * Signals that a Lambda invocation has started.
     */
    public static function invocationStarted(): void
    {
        // If the first invocation had happened already, then we are starting a 2nd invocation (or more)
        // so we are no longer in the cold start invocation anymore
        if (self::$hasFirstInvocationStarted) {
            self::$currentInvocationIsColdStart = false;
            return;
        }

        self::$hasFirstInvocationStarted = true;

        if (self::$currentInvocationIsColdStart) {
            // There was a cold start, let's figure out if it was a proactive initialization
            $timeElapsedSinceColdStartEnded = microtime(true) - self::$coldStartEndedTime;
            // If more than 100ms have passed since the cold start ended, we can assume the
            // Lambda sandbox was paused/frozen between the cold start and the first invocation
            // (100ms is an arbitrary value, we could use a lower value but I want to be conservative)
            // That means the Lambda sandbox was initialized proactively
            self::$wasProactiveInitialization = $timeElapsedSinceColdStartEnded > 0.1;
        }
    }

    /**
     * Timestamp of the beginning of the cold start.
     */
    public static function getColdStartBeginningTime(): float
    {
        return self::$coldStartBeginningTime;
    }

    /**
     * Timestamp of the end of the cold start.
     */
    public static function getColdStartEndedTime(): float
    {
        return self::$coldStartEndedTime;
    }

    /**
     * Returns `true` if the current Lambda invocation contained a cold start.
     *
     * This is `true` even if the cold start was a proactive initialization.
     *
     * This is no longer `true` once the second invocation (and subsequent invocations) start.
     */
    public static function currentInvocationIsColdStart(): bool
    {
        return self::$currentInvocationIsColdStart;
    }

    /**
     * Returns `true` if the current Lambda invocation contains a cold start that was "user-facing".
     *
     * "User-facing" means that the cold start duration was part of the invocation duration that the
     * invoker of the Lambda function experienced.
     *
     * For example, if the application is a web application, a "user-facing" cold start of 1 second
     * means that the response time of the first request contained a 1 second delay.
     */
    public static function currentInvocationIsUserFacingColdStart(): bool
    {
        return self::currentInvocationIsColdStart() && ! self::wasProactiveInitialization();
    }

    /**
     * Returns `true` if this Lambda sandbox was initialized proactively.
     */
    public static function wasProactiveInitialization(): bool
    {
        if (! isset(self::$wasProactiveInitialization)) {
            throw new Exception('Bref runtime error: it is too early to determine if the initialization was proactive, this method can only be called after the first invocation has started');
        }

        return self::$wasProactiveInitialization;
    }
}
