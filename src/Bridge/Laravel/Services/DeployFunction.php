<?php

namespace Bref\Bridge\Laravel\Services;


use Bref\Bridge\Laravel\Events\DeploymentRequested;
use Symfony\Component\Process\Process;

class DeployFunction
{

    public function handle(DeploymentRequested $event)
    {
        $process = new Process(sprintf('sam deploy --template-file .stack.yaml --capabilities CAPABILITY_IAM --stack-name %s',
            config('bref.name')));
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(600);
        $process->start();

        foreach ($process as $type => $data) {
            echo $data;
        }
    }
}
