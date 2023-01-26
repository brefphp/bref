<?php declare(strict_types=1);

namespace Bref\Secrets;

use AsyncAws\Ssm\SsmClient;
use AsyncAws\Ssm\ValueObject\Parameter;
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

        $ssm = $ssmClient ?? new SsmClient([
            'region' => $_ENV['AWS_REGION'] ?? $_ENV['AWS_DEFAULT_REGION'],
        ]);

        /** @var Parameter[] $parameters */
        $parameters = [];
        $parametersNotFound = [];
        // The API only accepts up to 10 parameters at a time, so we batch the calls
        foreach (array_chunk(array_values($ssmNames), 10) as $batchOfSsmNames) {
            try {
                $result = $ssm->getParameters([
                    'Names' => $batchOfSsmNames,
                    'WithDecryption' => true,
                ]);
                $parameters = array_merge($parameters, $result->getParameters());
            } catch (RuntimeException $e) {
                if ($e->getCode() === 400) {
                    // Extra descriptive error message for the most common error
                    throw new RuntimeException("Bref was not able to resolve secrets contained in environment variables from SSM because of a permissions issue with the SSM API. Did you add IAM permissions in serverless.yml to allow Lambda to access SSM? (docs: https://bref.sh/docs/environment/variables.html#at-deployment-time).\nFull exception message: {$e->getMessage()}");
                }
                throw $e;
            }
            $parametersNotFound = array_merge($parametersNotFound, $result->getInvalidParameters());
        }
        if (count($parametersNotFound) > 0) {
            throw new RuntimeException('The following SSM parameters could not be found: ' . implode(', ', $parametersNotFound));
        }

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
