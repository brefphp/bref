<?php

namespace Bref\Bridge\Laravel\Services;


use Bref\Bridge\Laravel\Events\UpdateRequested;
use Symfony\Component\Process\Process;

class UpdateFunction
{
    public function handle(UpdateRequested $event)
    {
        $this->runUpdate(config('bref.website_name'));
        $this->runUpdate(config('bref.artisan_name'));
    }

    protected function runUpdate(string $functionName)
    {
        $process = new Process([
                'aws',
                'lambda update-function-code',
                '--function-name',
                $functionName,
                '--zip-file',
                'fileb://storage/latest.zip'
            ]
        );
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(600);
        $process->start();

        foreach ($process as $type => $data) {
            echo $data;
        }
    }
}
