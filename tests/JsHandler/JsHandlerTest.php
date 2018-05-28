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
        self::assertEquals("Hello world!\n", $process->getOutput());
        self::assertEquals('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
        self::assertLambdaResponse(null);
        self::assertLambdaError(null);
    }

    public function test PHP stderr is forwarded to Node stdout()
    {
        $process = $this->runFile('bref.stderr.php');
        self::assertEquals("[STDERR] Hello world!\n", $process->getOutput());
        self::assertEquals('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
        self::assertLambdaResponse(null);
        self::assertLambdaError(null);
    }

    public function test PHP handler can return a response()
    {
        $process = $this->runFile('bref.array-response.php');
        self::assertEquals('', $process->getOutput());
        self::assertEquals('', $process->getErrorOutput());
        self::assertEquals(0, $process->getExitCode());
        self::assertLambdaResponse(['hello' => 'world']);
        self::assertLambdaError(null);
    }

    private function runFile($file)
    {
        $process = new Process('node runner.js');
        $process->setEnv([
            'LAMBDA_TASK_ROOT' => __DIR__,
            'TMP_DIRECTORY' => __DIR__ . '/tmp',
            'PHP_HANDLER' => $file,
        ]);
        $process->run();

        return $process;
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
