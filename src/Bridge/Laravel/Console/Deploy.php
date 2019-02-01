<?php
/**
 * Created by PhpStorm.
 * User: bubba
 * Date: 2019-01-31
 * Time: 16:27
 */

namespace Bref\Bridge\Laravel\Console;

use Symfony\Component\Process\Process;
use Bref\Bridge\Laravel\Package\Archive;
use Illuminate\Console\Command;

class Deploy extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bref:deploy';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Package (zip) the application in preparation for deployment, upload it to S3, and generate the .stack.yaml';

    public function handle(): int {

        $process = new Process(sprintf('sam deploy --template-file .stack.yaml --capabilities CAPABILITY_IAM --stack-name %s', env('APP_NAME')));
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(600);
        $process->start();

        foreach ($process as $type => $data) {
                echo $data;
        }
        return 0;
    }
}

