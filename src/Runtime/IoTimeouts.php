<?php declare(strict_types=1);

namespace Bref\Runtime;

use Bref\Context\Context;

/**
 * Network reads (HTTP calls, Redis, MySQL queries…) that would still be waiting when the Lambda invocation times out
 * fail with a timeout error shortly before, instead: the application can log and report the error,
 * which is lost when Lambda stops the invocation.
 *
 * The PHP settings are lowered to the time left in the invocation, minus a margin. They are never raised.
 *
 * @internal
 */
final class IoTimeouts
{
    /**
     * Before the invocation times out, for the application to handle the error.
     * PHP-FPM is stopped 1 second before the timeout (see FpmHandler), which leaves 1 second.
     */
    private const MARGIN_IN_MS = 2000;

    private const SETTINGS = [
        // PHP streams (file_get_contents(), sockets…), phpredis, Predis, Symfony HttpClient
        'default_socket_timeout',
        // MySQL queries (PDO and mysqli)
        'mysqlnd.net_read_timeout',
    ];

    /**
     * The values configured in php.ini, read before Bref changes them.
     *
     * @var array<string, int>|null
     */
    private static ?array $configured = null;

    /**
     * @return array<string, string> The settings to apply for the invocation.
     */
    public static function forInvocation(Context $context): array
    {
        $secondsLeft = max(1, intdiv($context->getRemainingTimeInMillis() - self::MARGIN_IN_MS, 1000));

        $settings = [];
        foreach (self::configured() as $name => $configured) {
            // 0 or less: no timeout configured
            $settings[$name] = (string) ($configured > 0 ? min($configured, $secondsLeft) : $secondsLeft);
        }

        return $settings;
    }

    /**
     * Apply the settings to the current process, for the duration of an invocation.
     */
    public static function apply(Context $context): void
    {
        foreach (self::forInvocation($context) as $name => $value) {
            ini_set($name, $value);
        }
    }

    /**
     * Restore the configured settings, after an invocation.
     */
    public static function restore(): void
    {
        foreach (self::configured() as $name => $configured) {
            ini_set($name, (string) $configured);
        }
    }

    /**
     * @return array<string, int>
     */
    private static function configured(): array
    {
        if (self::$configured === null) {
            self::$configured = [];
            foreach (self::SETTINGS as $name) {
                $value = ini_get($name);
                // The extension is not loaded
                if ($value === false) {
                    continue;
                }
                self::$configured[$name] = (int) $value;
            }
        }

        return self::$configured;
    }
}
