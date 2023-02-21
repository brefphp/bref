<?php declare(strict_types=1);

namespace Bref;

use Bref\Secrets\Secrets;
use Exception;

/**
 * Checks environment variables for the "bref-ssm:xxx" syntax.
 *
 * If found, it ensures the "bref/secrets-loader" package is installed
 * and then loads the secrets.
 *
 * That lets us make the "bref/secrets-loader" package an optional dependency.
 *
 * @internal
 */
final class LazySecretsLoader
{
    public static function loadSecretEnvironmentVariables(): void
    {
        if (! self::areThereSecretsToLoad()) {
            return;
        }

        if (! class_exists(Secrets::class)) {
            throw new Exception('The "bref/secrets-loader" package is required to load SSM parameters via the "bref-ssm:xxx" syntax in environment variables. Please add it to your "require" section in composer.json.');
        }

        Secrets::loadSecretEnvironmentVariables();
    }

    /**
     * Checks if there are any environment variable that starts with "bref-ssm:".
     */
    private static function areThereSecretsToLoad(): bool
    {
        /** @var array<string,string>|string|false $envVars */
        $envVars = getenv(local_only: true); // @phpstan-ignore-line PHPStan is wrong
        if (! is_array($envVars)) {
            return false;
        }

        // Only consider environment variables that start with "bref-ssm:"
        $envVarsToDecrypt = array_filter($envVars, function (string $value): bool {
            return str_starts_with($value, 'bref-ssm:');
        });

        return ! empty($envVarsToDecrypt);
    }
}
