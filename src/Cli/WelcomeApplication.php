<?php declare(strict_types=1);

namespace Bref\Cli;

use Silly\Application;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Default CLI handler that shows a "welcome" message.
 */
class WelcomeApplication extends Application
{
    public function __construct()
    {
        parent::__construct();

        $this->command('hello', function (SymfonyStyle $io): void {
            $io->writeln('<comment>Welcome! This CLI application is working but has no commands.</comment>');
            $io->writeln([
                'Add your own CLI application by registering a Symfony Console application'
                . ' (or a Silly application) using the <info>$application->cliHandler(...)</info> method.',
            ]);
        });

        $this->setDefaultCommand('hello');
    }

    /**
     * Disable the default commands (help and list).
     */
    protected function getDefaultCommands(): array
    {
        return [];
    }
}
