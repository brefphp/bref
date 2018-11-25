<?php declare(strict_types=1);

namespace Bref\Test\Cli;

use Bref\Cli\InvokeCommand;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InvokeCommandTest extends TestCase
{
    /** @var  InputInterface */
    public $inputInterface;

    /** @var  OutputInterface */
    public $outputInterface;

    /** @var  InvokeCommand */
    public static $invokeCommand;

    public const INVALID_JSON = '/';
    public const VALID_JSON = '{}';

    public static function setUpBeforeClass()
    {
        self::$invokeCommand = new InvokeCommand(function () {
            return function ($event): void {
            };
        });
    }

    public function setUp()
    {
        $this->inputInterface = $this->prophesize(InputInterface::class);
        $this->inputInterface->bind(Argument::any())->willReturn(null);
        $this->inputInterface->isInteractive()->willReturn(false);
        $this->inputInterface->hasArgument(Argument::any())->willReturn(false);
        $this->inputInterface->validate()->willReturn(null);
        $this->outputInterface = $this->prophesize(OutputInterface::class);
    }

    public function test event json_decode fail()
    {
        $this->expectException(\RuntimeException::class);
        $this->inputInterface->getOption(Argument::exact('event'))->willReturn(self::INVALID_JSON);
        self::$invokeCommand->run($this->inputInterface->reveal(), $this->outputInterface->reveal());
    }

    public function test event json_decode success()
    {
        $this->inputInterface->getOption(Argument::exact('event'))->willReturn(self::VALID_JSON);
        $this->inputInterface->getOption(Argument::exact('path'))->willReturn(null);
        $this->outputInterface->writeln(Argument::any())->shouldBeCalled(1);
        self::$invokeCommand->run($this->inputInterface->reveal(), $this->outputInterface->reveal());
    }
}
