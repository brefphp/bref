<?php declare(strict_types=1);

namespace Bref\Bridge\Laravel\Services;

use Bref\Bridge\Laravel\Events\DeploymentRequested;
use Symfony\Component\Process\Process;

class DeployFunction
{
    public function handle(DeploymentRequested $event): void
    {
        $process = new Process([
            'sam',
            'deploy',
            '--template-file',
            '.stack.yaml',
            '--capabilities',
            'CAPABILITY_IAM',
            '--stack-name',
            config('bref.name'),
        ]);
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(600);
        $process->start();

        foreach ($process as $type => $data) {
            echo $data;
        }
    }
}
