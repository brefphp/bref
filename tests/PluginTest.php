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

        self::assertFunction($output['functions']['function'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:php-83:',
        ]);
        self::assertFunction($output['functions']['fpm'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:php-83-fpm:',
        ]);
        self::assertFunction($output['functions']['console'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:php-83:',
            'arn:aws:lambda:us-east-1:873528684822:layer:console:',
        ]);

        self::assertFunction($output['functions']['function-arm'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:arm-php-83:',
        ]);
        self::assertFunction($output['functions']['fpm-arm'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:arm-php-83-fpm:',
        ]);
        self::assertFunction($output['functions']['console-arm'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:arm-php-83:',
            'arn:aws:lambda:us-east-1:873528684822:layer:console:',
        ]);
    }

    public function test the plugin adds the layers when the runtime is set in the provider(): void
    {
        $output = $this->slsPrint('serverless-runtime-root.yml');

        self::assertFunction($output['functions']['function'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:php-83:',
        ]);
        self::assertFunction($output['functions']['function-arm'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:arm-php-83:',
        ]);
    }

    public function test the plugin doesnt break layers added separately(): void
    {
        $output = $this->slsPrint('serverless-with-layers.yml');

        self::assertFunction($output['functions']['function'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:php-83:',
            'arn:aws:lambda:us-east-1:1234567890:layer:foo:1',
        ]);
        self::assertFunction($output['functions']['function-arm'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:arm-php-83:',
            'arn:aws:lambda:us-east-1:1234567890:layer:foo:1',
        ]);
        self::assertFunction($output['functions']['function-with-layers'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:php-83:',
            // This function doesn't have the `foo` layer because that's how SF works:
            // layers in the function completely override the layers in the root
            'arn:aws:lambda:us-east-1:1234567890:layer:bar:1',
        ]);
        self::assertFunction($output['functions']['function-arm-with-layers'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:arm-php-83:',
            // This function doesn't have the `foo` layer because that's how SF works:
            // layers in the function completely override the layers in the root
            'arn:aws:lambda:us-east-1:1234567890:layer:bar:1',
        ]);
    }

    public function test the plugin doesnt break layers added separately with the runtime set at the root(): void
    {
        $output = $this->slsPrint('serverless-runtime-root-with-layers.yml');

        self::assertFunction($output['functions']['function'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:php-83:',
            'arn:aws:lambda:us-east-1:1234567890:layer:foo:1',
        ]);
        self::assertFunction($output['functions']['function-arm'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:arm-php-83:',
            'arn:aws:lambda:us-east-1:1234567890:layer:foo:1',
        ]);
        self::assertFunction($output['functions']['function-with-layers'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:php-83:',
            // This function doesn't have the `foo` layer because that's how SF works:
            // layers in the function completely override the layers in the root
            'arn:aws:lambda:us-east-1:1234567890:layer:bar:1',
        ]);
        self::assertFunction($output['functions']['function-arm-with-layers'], [
            'arn:aws:lambda:us-east-1:873528684822:layer:arm-php-83:',
            // This function doesn't have the `foo` layer because that's how SF works:
            // layers in the function completely override the layers in the root
            'arn:aws:lambda:us-east-1:1234567890:layer:bar:1',
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

    private static function assertFunction(array $config, array $layers): void
    {
        self::assertEquals('provided.al2', $config['runtime']);
        self::assertCount(count($layers), $config['layers'], sprintf('Expected %d layers, got %d: %s', count($layers), count($config['layers']), json_encode($config['layers'], JSON_THROW_ON_ERROR)));
        foreach ($layers as $index => $layer) {
            self::assertStringStartsWith($layer, $config['layers'][$index]);
        }
    }
}
