<?php declare(strict_types=1);

namespace Bref\Console\Command;

use Bref\Bref;
use Bref\Context\Context;
use Bref\Runtime\Invoker;
use Exception;
use JsonException;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Yaml\Yaml;
use Throwable;

/**
 * Local function invocation.
 */
class Local
{
    public function __invoke(string $function, ?string $data, ?string $file, SymfonyStyle $io): int
    {
        if ($data && $file) {
            throw new Exception('You cannot provide both event data and the --file= option.');
        }

        if (! file_exists('serverless.yml')) {
            throw new Exception('No `serverless.yml` file found.');
        }

        $handler = $this->resolveHandler($function);

        if ($file) {
            if (! file_exists($file)) {
                throw new Exception("The file '$file' does not exist.");
            }
            $data = file_get_contents($file);
        }

        try {
            $event = $data ? json_decode($data, true, 512, JSON_THROW_ON_ERROR) : null;
        } catch (JsonException $e) {
            throw new Exception('The JSON provided for the event data is invalid JSON.');
        }

        // Same configuration as the Bref runtime on Lambda
        ini_set('display_errors', '1');
        error_reporting(E_ALL);

        $requestId = '8f507cfc-example-4697-b07a-ac58fc914c95';
        $startTime = $this->logStart($io, $requestId);

        try {
            $invoker = new Invoker;
            $result = $invoker->invoke($handler, $event, new Context($requestId, 0, '', ''));
        } catch (Throwable $e) {
            $io->error($e->getMessage());
            return 1;
        }

        $this->logEnd($startTime, $io, $requestId);
        // Show the invocation result
        $io->block(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), null, 'fg=black;bg=green', '', true);

        return 0;
    }

    /**
     * @return mixed
     */
    private function resolveHandler(string $function)
    {
        $serverlessConfig = Yaml::parseFile('serverless.yml');

        if (! isset($serverlessConfig['functions'][$function])) {
            throw new Exception("There is no function named '$function' in serverless.yml");
        }
        if (! isset($serverlessConfig['functions'][$function]['handler'])) {
            throw new Exception("There is no handler defined on function '$function' in serverless.yml");
        }

        $handlerName = $serverlessConfig['functions'][$function]['handler'];

        try {
            return Bref::getContainer()->get($handlerName);
        } catch (NotFoundExceptionInterface $e) {
            throw new Exception($e->getMessage() . PHP_EOL . 'Reminder: `bref local` can invoke functions that use the FUNCTION runtime, not the HTTP (or "FPM") runtime. If you are unsure, check out https://bref.sh/docs/local-development.html#http-applications to run HTTP applications locally.');
        }
    }

    private function logStart(SymfonyStyle $io, string $requestId): float
    {
        $io->writeln("START RequestId: $requestId Version: \$LATEST");
        return microtime(true);
    }

    private function logEnd(float $startTime, SymfonyStyle $io, string $requestId): void
    {
        $duration = ceil((microtime(true) - $startTime) * 1000);
        $billedDuration = ceil(max($duration / 100, 1)) * 100;
        $memoryUsed = ceil(memory_get_usage() / 1024 / 1024);

        $io->writeln([
            "END RequestId: $requestId",
            "REPORT RequestId: $requestId Duration: $duration ms Billed Duration: $billedDuration ms Memory Size: 1024 MB Max Memory Used: $memoryUsed MB",
        ]);
    }
}
