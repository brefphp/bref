<?php declare(strict_types=1);

namespace Bref\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class CliTest extends TestCase
{
    public function test the cli command boots()
    {
        $process = new Process([__DIR__ . '/../bref'], __DIR__ . '/Fixture/EmptyDirectory');
        $process->mustRun();
        self::assertNotEmpty($process->getOutput());
    }
}
