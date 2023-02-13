<?php declare(strict_types=1);

namespace Bref\Test\ConsoleRuntime;

use Bref\Bref;
use Bref\ConsoleRuntime\Main;
use Exception;
use PHPUnit\Framework\TestCase;

class MainTest extends TestCase
{
    public function test startup hook is called()
    {
        Bref::beforeStartup(function () {
            throw new Exception('This should be called');
        });

        $this->expectExceptionMessage('This should be called');
        Main::run();
    }
}
