<?php
declare(strict_types=1);

namespace Bref\Test\JsHandler;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

class JsHandlerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        (new Filesystem)->remove(__DIR__ . '/tmp/*.json');
    }

    public function test PHP stdout is forwarded to Node stdout()
    {
        $process = $this->runFile('bref.stdout.php');
        self::assertProcessResult($process, "Hello world!\n");
        self::assertLambdaResponse(null);
        self::assertLambdaError(null);
    }

    public function test PHP stderr is forwarded to Node stdout()
    {
        $process = $this->runFile('bref.stderr.php');
        self::assertProcessResult($process, "[STDERR] Hello world!\n");
        self::assertLambdaResponse(null);
        self::assertLambdaError(null);
    }

    public function test PHP handler can return a response()
    {
        $process = $this->runFile('bref.array-response.php');
        self::assertProcessResult($process, '');
        self::assertLambdaResponse(['hello' => 'world']);
        self::assertLambdaError(null);
    }

    public function test PHP handler receives the lambda event()
    {
        $event = [
            'key' => 'world',
        ];

        $process = $this->runFile('bref.array-response.php', $event);
        self::assertProcessResult($process, '');
        self::assertLambdaResponse(['hello' => 'world']);
        self::assertLambdaError(null);
    }

    public function test PHP errors are forwarded by Node()
    {
        $process = $this->runFile('bref.error.php');
        self::assertProcessResult($process, "Oh no!\n");
        self::assertLambdaResponse(null);
        self::assertLambdaError([]);
    }

    private function runFile(string $file, array $event = [])
    {
        $process = new Process('node runner.js ' . escapeshellarg(json_encode($event)));
        $process->setEnv([
            'LAMBDA_TASK_ROOT' => __DIR__,
            'TMP_DIRECTORY' => __DIR__ . '/tmp',
            'PHP_HANDLER' => $file,
        ]);
        $process->run();

        return $process;
    }

    private static function assertProcessResult(
        Process $process,
        string $stdout,
        string $stderr = '',
        int $exitCode = 0
    ) {
        $fullOutput = $process->getOutput() . $process->getErrorOutput();

        self::assertEquals($exitCode, $process->getExitCode(), $fullOutput);
        self::assertEquals($stdout, $process->getOutput());
        self::assertEquals($stderr, $process->getErrorOutput());
    }

    private static function assertLambdaResponse($expected) : void
    {
        $response = json_decode(file_get_contents(__DIR__ . '/tmp/testResponse.json'), true);
        self::assertEquals($expected, $response);
    }

    private static function assertLambdaError($expected) : void
    {
        $error = json_decode(file_get_contents(__DIR__ . '/tmp/testError.json'), true);
        self::assertEquals($expected, $error);
    }
}
