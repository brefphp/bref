<?php
/**
 * Created by PhpStorm.
 * User: bubba
 * Date: 2019-02-02
 * Time: 16:24
 */

namespace Bref\Bridge\Laravel\Console;

use Symfony\Component\Process\Process;
use Bref\Bridge\Laravel\Package\Archive;
use Illuminate\Console\Command;

class Update extends Command
{
/**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bref:update-lambda-code';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates the code on lambda.';

    public function handle(): int {

        $process = new Process(
            sprintf('aws lambda update-function-code --function-name %s-apigateway --zip-file fileb://storage/latest.zip', env('APP_NAME'))
        );
        $process->setWorkingDirectory(base_path());
        $process->setTimeout(600);
        $process->start();

        foreach ($process as $type => $data) {
                echo $data;
        }
        return 0;
    }
}
