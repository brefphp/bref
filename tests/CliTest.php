<?php
declare(strict_types=1);

namespace Bref\Test;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class CliTest extends TestCase
{
    public function test deploying requires mandatory files to exist()
    {
        $this->expectException(ProcessFailedException::class);
        $this->expectExceptionMessage('The files `bref.php` and `serverless.yml` are required to deploy');

        $process = new Process(__DIR__ . '/../bref deploy', __DIR__ . '/Fixture/EmptyDirectory');
        $process->mustRun();
    }
}
