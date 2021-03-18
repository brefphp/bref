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
    public const SIGNATURE = 'local [function] [data] [--file=] [--handler=] [--config=]';

    public function __invoke(?string $function, ?string $data, ?string $file, ?string $handler, ?string $config, SymfonyStyle $io): int
    {
        if ($function === null && $handler === null) {
            throw new Exception('Please provide a function name or the --handler= option.');
        }
        if ($function && $data && $handler) {
            throw new Exception('You cannot provide both a function name and the --handler= option.');
        }

        if ($config !== null && ! file_exists($config)) {
            throw new Exception("The serverless file '$config' does not exist.");
        }

        if ($handler) {
            // Shift the arguments since there is no function passed
            $data = $function;
        } else {
            $handler = $this->handlerFromServerlessYml($function, $config);
        }

        if ($data && $file) {
            throw new Exception('You cannot provide both event data and the --file= option.');
        }

        try {
            $handler = Bref::getContainer()->get($handler);
        } catch (NotFoundExceptionInterface $e) {
            throw new Exception($e->getMessage() . PHP_EOL . 'Reminder: `bref local` can invoke event-driven functions that use the FUNCTION runtime, not the web app (or "FPM") runtime. Check out https://bref.sh/docs/web-apps/local-development.html to run web applications locally.');
        }

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
            $io->writeln([
                get_class($e) . ': ' . $e->getMessage(),
                'Stack trace:',
                $e->getTraceAsString(),
            ]);
            $io->error($e->getMessage());
            return 1;
        }

        $this->logEnd($startTime, $io, $requestId);
        // Show the invocation result
        $io->block(json_encode($result, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT), null, 'fg=black;bg=green', '', true);

        return 0;
    }

    private function handlerFromServerlessYml(string $function, ?string $config): string
    {
        $file = $config ?? 'serverless.yml';
        if (! file_exists($file)) {
            throw new Exception("No `serverless.yml` file was found to resolve function $function.\nIf you do not use serverless.yml, pass the handler via the `--handler` option: vendor/bin/bref local --handler=file.php\nIf your serverless.yml file is stored elsewhere, use the `--config` option: vendor/bin/bref local --config=foo/serverless.yml");
        }

        $serverlessConfig = Yaml::parseFile($file, Yaml::PARSE_CUSTOM_TAGS);

        if (! isset($serverlessConfig['functions'][$function])) {
            throw new Exception("There is no function named '$function' in serverless.yml");
        }
        if (! isset($serverlessConfig['functions'][$function]['handler'])) {
            throw new Exception("There is no handler defined on function '$function' in serverless.yml");
        }

        return $serverlessConfig['functions'][$function]['handler'];
    }

    private function logStart(SymfonyStyle $io, string $requestId): float
    {
        $io->writeln("START RequestId: $requestId Version: \$LATEST");
        return microtime(true);
    }

    private function logEnd(float $startTime, SymfonyStyle $io, string $requestId): void
    {
        $duration = ceil((microtime(true) - $startTime) * 1000);
        $memoryUsed = ceil(memory_get_usage() / 1024 / 1024);

        $io->writeln([
            "END RequestId: $requestId",
            "REPORT RequestId: $requestId Duration: $duration ms Billed Duration: $duration ms Memory Size: 1024 MB Max Memory Used: $memoryUsed MB",
        ]);
    }
}
