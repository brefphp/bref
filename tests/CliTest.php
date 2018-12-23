<?php declare(strict_types=1);

namespace Bref\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class CliTest extends TestCase
{
    /**
     * @expectedException \Symfony\Component\Process\Exception\ProcessFailedException
     * @expectedExceptionMessage The file `template.yaml` is required to deploy
     */
    public function test deploying requires mandatory files to exist()
    {
        $process = new Process([__DIR__ . '/../bref', 'deploy'], __DIR__ . '/Fixture/EmptyDirectory');
        $process->mustRun();
    }
}
