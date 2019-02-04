<?php declare(strict_types=1);

namespace Bref\Bridge\Laravel\Console;

use Bref\Bridge\Laravel\Events\SamConfigurationRequested;
use Illuminate\Console\Command;

class ConfigSam extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bref:config-sam';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure the SAM Template.';

    public function handle(): int
    {
        event(new SamConfigurationRequested);
        return 0;
    }
}
