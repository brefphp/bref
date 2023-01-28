<?php declare(strict_types=1);

namespace Bref\Secrets;

use AsyncAws\Ssm\SsmClient;
use Closure;
use RuntimeException;

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

        $actuallyCalledSsm = false;
        $parameters = self::readParametersFromCacheOr(function () use ($ssmClient, $ssmNames, &$actuallyCalledSsm) {
            $actuallyCalledSsm = true;
            return self::retrieveParametersfromSsm($ssmClient, array_values($ssmNames));
        });

        foreach ($parameters as $parameterName => $parameterValue) {
            $envVar = array_search($parameterName, $ssmNames, true);
            $_SERVER[$envVar] = $_ENV[$envVar] = $parameterValue;
            putenv("$envVar=$parameterValue");
        }

        // Only log once (when the cache was empty) else it might spam the logs in the function runtime
        // (where the process restarts on every invocation)
        if ($actuallyCalledSsm) {
            $stderr = fopen('php://stderr', 'ab');
            fwrite($stderr, '[Bref] Loaded these environment variables from SSM: ' . implode(', ', array_keys($envVarsToDecrypt)) . PHP_EOL);
        }
    }

    /**
     * Cache the parameters in a temp file.
     * Why? Because on the function runtime, the PHP process might
     * restart on every invocation (or on error), so we don't want to
     * call SSM every time.
     *
     * @param Closure(): array<string, string> $paramResolver
     * @return array<string, string> Map of parameter name -> value
     */
    private static function readParametersFromCacheOr(Closure $paramResolver): array
    {
        // Check in cache first
        $cacheFile = sys_get_temp_dir() . '/bref-ssm-parameters.php';
        if (is_file($cacheFile)) {
            $parameters = json_decode(file_get_contents($cacheFile), true);
            if (is_array($parameters)) {
                return $parameters;
            }
        }

        // Not in cache yet: we resolve it
        $parameters = $paramResolver();

        // Using json_encode instead of var_export due to possible security issues
        file_put_contents($cacheFile, json_encode($parameters));

        return $parameters;
    }

    /**
     * @param string[] $ssmNames
     * @return array<string, string> Map of parameter name -> value
     */
    private static function retrieveParametersfromSsm(?SsmClient $ssmClient, array $ssmNames): array
    {
        $ssm = $ssmClient ?? new SsmClient([
            'region' => $_ENV['AWS_REGION'] ?? $_ENV['AWS_DEFAULT_REGION'],
        ]);

        /** @var array<string, string> $parameters Map of parameter name -> value */
        $parameters = [];
        $parametersNotFound = [];

        // The API only accepts up to 10 parameters at a time, so we batch the calls
        foreach (array_chunk($ssmNames, 10) as $batchOfSsmNames) {
            try {
                $result = $ssm->getParameters([
                    'Names' => $batchOfSsmNames,
                    'WithDecryption' => true,
                ]);
                foreach ($result->getParameters() as $parameter) {
                    $parameters[$parameter->getName()] = $parameter->getValue();
                }
            } catch (RuntimeException $e) {
                if ($e->getCode() === 400) {
                    // Extra descriptive error message for the most common error
                    throw new RuntimeException(
                        "Bref was not able to resolve secrets contained in environment variables from SSM because of a permissions issue with the SSM API. Did you add IAM permissions in serverless.yml to allow Lambda to access SSM? (docs: https://bref.sh/docs/environment/variables.html#at-deployment-time).\nFull exception message: {$e->getMessage()}",
                        $e->getCode(),
                        $e,
                    );
                }
                throw $e;
            }
            $parametersNotFound = array_merge($parametersNotFound, $result->getInvalidParameters());
        }

        if (count($parametersNotFound) > 0) {
            throw new RuntimeException('The following SSM parameters could not be found: ' . implode(', ', $parametersNotFound));
        }

        return $parameters;
    }
}
