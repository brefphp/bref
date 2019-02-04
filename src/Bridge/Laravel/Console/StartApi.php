<?php
/**
 * Created by PhpStorm.
 * User: bubba
 * Date: 2019-02-01
 * Time: 14:17
 */

namespace Bref\Bridge\Laravel\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class StartApi extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bref:start-api';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Starts up the SAM Local API for testing.';

    public function handle(): int
    {
        $process = new Process(['sam', 'local', 'start-api']);
        $process->setWorkingDirectory(base_path());
        $process->start();

        foreach ($process as $type => $data) {
            echo $data;
        }
        return 0;
    }

}
