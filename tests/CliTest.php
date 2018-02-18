<?php
declare(strict_types=1);

namespace Bref\Test;

use Bref\Util\CommandRunner;
use PHPUnit\Framework\TestCase;

class CliTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage The files `bref.php` and `serverless.yml` are required to deploy
     */
    public function test deploying requires mandatory files to exist()
    {
        $commandRunner = new CommandRunner;
        $directory = __DIR__ . '/Fixture/EmptyDirectory';
        $cliPath = __DIR__ . '/../bref';
        $commandRunner->run("cd $directory && $cliPath deploy");
    }
}
