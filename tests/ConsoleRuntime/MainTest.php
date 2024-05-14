<?php declare(strict_types=1);

namespace Bref\Test\ConsoleRuntime;

use Bref\Bref;
use Bref\ConsoleRuntime\CommandFailed;
use Bref\ConsoleRuntime\Main;
use Bref\Test\RuntimeTestCase;
use Exception;

class MainTest extends RuntimeTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        putenv('LAMBDA_TASK_ROOT=' . __DIR__);
        putenv('_HANDLER=console.php');
    }

    public function test startup hook is called()
    {
        Bref::beforeStartup(function () {
            throw new Exception('This should be called');
        });

        $this->expectExceptionMessage('This should be called');
        Main::run();
    }

    public function test happy path()
    {
        $this->givenAnEvent('');

        try {
            Main::run();
        } catch (Exception) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $this->assertInvocationResult([
            'exitCode' => 0,
            'output' => "Hello world!\n",
        ]);
    }

    public function test failure()
    {
        $this->givenAnEvent('fail');

        try {
            Main::run();
        } catch (Exception) {
            // Needed because `run()` is an infinite loop and will fail eventually
        }

        $this->assertInvocationErrorResult(CommandFailed::class, "Hello world!\nFailure\n");
    }
}
