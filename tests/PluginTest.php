<?php declare(strict_types=1);

namespace Bref\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class PluginTest extends TestCase
{
    public function test the plugin adds the layers(): void
    {
        $output = $this->slsPrint('serverless.yml');

        self::assertFunction($output['functions']['function'], 'provided.al2', [
            'arn:aws:lambda:us-east-1:534081306603:layer:php-83:',
        ]);
        self::assertFunction($output['functions']['fpm'], 'provided.al2', [
            'arn:aws:lambda:us-east-1:534081306603:layer:php-83-fpm:',
        ]);
        self::assertFunction($output['functions']['console'], 'provided.al2', [
            'arn:aws:lambda:us-east-1:534081306603:layer:php-83:',
            'arn:aws:lambda:us-east-1:534081306603:layer:console:',
        ]);

        self::assertFunction($output['functions']['function-arm'], 'provided.al2', [
            'arn:aws:lambda:us-east-1:534081306603:layer:arm-php-83:',
        ]);
        self::assertFunction($output['functions']['fpm-arm'], 'provided.al2', [
            'arn:aws:lambda:us-east-1:534081306603:layer:arm-php-83-fpm:',
        ]);
        self::assertFunction($output['functions']['console-arm'], 'provided.al2', [
            'arn:aws:lambda:us-east-1:534081306603:layer:arm-php-83:',
            'arn:aws:lambda:us-east-1:534081306603:layer:console:',
        ]);
    }

    public function test the plugin adds the layers when the runtime is set in the provider(): void
    {
        $output = $this->slsPrint('serverless-runtime-root.yml');

        self::assertFunction($output['functions']['function'], 'provided.al2', [
            'arn:aws:lambda:us-east-1:534081306603:layer:php-83:',
        ]);
        self::assertFunction($output['functions']['function-arm'], 'provided.al2', [
            'arn:aws:lambda:us-east-1:534081306603:layer:arm-php-83:',
        ]);
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

    private static function assertFunction(array $config, string $runtime, array $layers): void
    {
        self::assertEquals($runtime, $config['runtime']);
        self::assertCount(count($layers), $config['layers']);
        foreach ($layers as $index => $layer) {
            self::assertStringStartsWith($layer, $config['layers'][$index]);
        }
    }
}
