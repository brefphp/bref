<?php declare(strict_types=1);

namespace Bref\Secrets;

use AsyncAws\Ssm\SsmClient;

class Secrets
{
    /**
     * Decrypt environment variables that are encrypted with AWS SSM.
     *
     * @param SsmClient $ssmClient To allow mocking in tests.
     */
    public static function decryptSecretEnvironmentVariables(?SsmClient $ssmClient = null): void
    {
        /** @var array<string,string>|string|false $envVars */
        $envVars = getenv(local_only: true); // @phpstan-ignore-line PHPStan is wrong
        if (! is_array($envVars)) {
            return;
        }

        // Only consider environment variables that start with "bref-ssm:"
        $envVarsToDecrypt = array_filter($envVars, function (string $value): bool {
            return str_starts_with($value, 'bref-ssm:');
        });
        if (empty($envVarsToDecrypt)) {
            return;
        }

        // Extract the SSM parameter names by removing the "bref-ssm:" prefix
        $ssmNames = array_map(function (string $value): string {
            return substr($value, strlen('bref-ssm:'));
        }, $envVarsToDecrypt);

        $ssm = $ssmClient ?? new SsmClient([
            'version' => 'latest',
            'region' => $_ENV['AWS_REGION'] ?? $_ENV['AWS_DEFAULT_REGION'],
        ]);

        $parameters = $ssm->getParameters([
            'Names' => array_values($ssmNames),
            'WithDecryption' => true,
        ])->getParameters();

        foreach ($parameters as $parameter) {
            $envVar = array_search($parameter->getName(), $ssmNames, true);
            $decryptedValue = $parameter->getValue();
            $_SERVER[$envVar] = $_ENV[$envVar] = $decryptedValue;
            putenv("$envVar=$decryptedValue");
        }

        $stderr = fopen('php://stderr', 'ab');
        fwrite($stderr, '[Bref] Loaded these environment variables from SSM: ' . implode(', ', array_keys($envVarsToDecrypt)) . PHP_EOL);
    }
}
