<?php declare(strict_types=1);

namespace Bref\Test\Cli;

use Bref\Cli\InvokeCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;

class InvokeCommandTest extends TestCase
{
    /** @var  InvokeCommand */
    public $command;

    public function setUp()
    {
        parent::setUp();

        $this->command = new InvokeCommand(function () {
            return function (array $event): string {
                return 'Hello ' . $event['foo'];
            };
        });
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The `--event` option provided contains invalid JSON: {fooo
     */
    public function test event json_decode fail()
    {
        $this->runCommandWithParameters([
            '--event' => '{fooo',
        ]);
    }

    public function test event json_decode success()
    {
        $output = $this->runCommandWithParameters([
            '--event' => '{"foo": "bar"}',
        ]);

        self::assertEquals('"Hello bar"', $output);
    }

    public function runCommandWithParameters(array $parameters): string
    {
        $output = new BufferedOutput;

        $this->command->run(new ArrayInput($parameters), $output);

        return trim($output->fetch());
    }
}
