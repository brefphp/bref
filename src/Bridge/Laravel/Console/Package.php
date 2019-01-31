<?php
/**
 * Created by PhpStorm.
 * User: bubba
 * Date: 2019-01-31
 * Time: 16:27
 */

namespace Bref\Bridge\Laravel\Console;


use Bref\Bridge\Laravel\Package\Archive;
use Illuminate\Console\Command;

class Package extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bref:package';
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Package (zip) the application in preparation for deployment.';

    public function handle(): int {
        $this->info('Creating Archive');
        $package = Archive::laravel();
        $this->info('Package at: ' . $package);
        return 0;
    }
}
