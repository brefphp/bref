<?php declare(strict_types=1);

namespace Bref\Test;

use Bref\ConsoleRuntime\Main as ConsoleMain;
use Bref\FpmRuntime\Main as FpmMain;
use Bref\FunctionRuntime\Main as FunctionMain;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class PluginTest extends TestCase
{
    public function test the plugin sets the Bref runtimes(): void
    {
        $output = $this->slsPrint('serverless.yml');

        self::assertFunction($output['functions']['function'], FunctionMain::class, 'php-83');
        self::assertFunction($output['functions']['fpm'], FpmMain::class, 'php-83');
        self::assertFunction($output['functions']['console'], ConsoleMain::class, 'php-83');

        // The same runtimes work on ARM
        self::assertFunction($output['functions']['function-arm'], FunctionMain::class, 'php-83');
        self::assertFunction($output['functions']['fpm-arm'], FpmMain::class, 'php-83');
        self::assertFunction($output['functions']['console-arm'], ConsoleMain::class, 'php-83');

        self::assertFunction($output['functions']['function-86'], FunctionMain::class, 'php-86');
    }

    public function test the plugin sets the Bref runtimes when the runtime is set in the provider(): void
    {
        $output = $this->slsPrint('serverless-runtime-root.yml');

        self::assertFunction($output['functions']['function'], FunctionMain::class, 'php-83');
        self::assertFunction($output['functions']['function-arm'], FunctionMain::class, 'php-83');
    }

    public function test the plugin doesnt touch the layers(): void
    {
        $output = $this->slsPrint('serverless-with-layers.yml');

        self::assertFunction($output['functions']['function'], FunctionMain::class, 'php-83');
        self::assertArrayNotHasKey('layers', $output['functions']['function']);
        self::assertSame(['arn:aws:lambda:us-east-1:1234567890:layer:foo:1'], $output['provider']['layers']);
        self::assertFunction($output['functions']['function-with-layers'], FunctionMain::class, 'php-83');
        self::assertSame(['arn:aws:lambda:us-east-1:1234567890:layer:bar:1'], $output['functions']['function-with-layers']['layers']);
    }

    public function test the plugin exposes the stack name(): void
    {
        $output = $this->slsPrint('serverless.yml');
        self::assertSame('bref-dev', $output['provider']['environment']['BREF_STACK_NAME']);

        $output = $this->slsPrint('serverless-stack-name.yml');
        self::assertSame('my-custom-stack', $output['provider']['environment']['BREF_STACK_NAME']);
    }

    public function test the plugin enables response streaming for streamed Function URLs and REST APIs(): void
    {
        $output = $this->slsPrint('serverless-response-streaming.yml');

        self::assertSame('1', $output['functions']['streamed']['environment']['BREF_RESPONSE_STREAMING']);
        self::assertArrayNotHasKey('BREF_RESPONSE_STREAMING', $output['functions']['buffered']['environment']);
        self::assertSame('1', $output['functions']['streamed-rest-api']['environment']['BREF_RESPONSE_STREAMING']);
    }

    public function test the plugin disables the retries of console functions(): void
    {
        $output = $this->slsPrint('serverless-console-retries.yml');

        self::assertSame(0, $output['functions']['console']['maximumRetryAttempts']);
        self::assertSame(0, $output['functions']['console-image']['maximumRetryAttempts']);
        self::assertSame(2, $output['functions']['console-with-retries']['maximumRetryAttempts']);
        self::assertArrayNotHasKey('maximumRetryAttempts', $output['functions']['function']);
    }

    private function slsPrint(string $configFile): array
    {
        $process = (new Process(
            ['serverless', 'print', '-c', $configFile],
            cwd: __DIR__ . '/Plugin',
            env: [
                'SLS_TELEMETRY_DISABLED' => '1', // else we sometimes get HTTP errors (and its faster)
            ],
        ))->mustRun();
        return Yaml::parse($process->getOutput());
    }

    private static function assertFunction(array $config, string $brefRuntime, string $runtime): void
    {
        self::assertEquals("arn:aws:lambda:us-east-1:873528684822:runtime/$runtime/alias/4", $config['runtime']);
        self::assertEquals($brefRuntime, $config['environment']['BREF_RUNTIME']);
    }
}
