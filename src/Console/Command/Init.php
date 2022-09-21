<?php declare(strict_types=1);

namespace Bref\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class Init extends Command
{
    /**
     * Symfony console input/output handler
     *
     * @var SymfonyStyle
     */
    private $io;

    /**
     * Absolute path pointing into project's
     * template directory
     *
     * @var string
     */
    private $rootPath;

    protected function configure(): void
    {
        $this->setName('init');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = $io = new SymfonyStyle($input, $output);

        $exeFinder = new ExecutableFinder;
        if (! $exeFinder->find('serverless')) {
            $io->warning(
                'The `serverless` command is not installed.' . PHP_EOL .
                'You will not be able to deploy your application unless it is installed' . PHP_EOL .
                'Please follow the instructions at https://bref.sh/docs/installation.html' . PHP_EOL .
                'If you have the `serverless` command available elsewhere (eg in a Docker container) you can ignore this warning'
            );
        }

        $choice = $io->choice(
            'What kind of lambda do you want to create? (you will be able to add more functions later by editing `serverless.yml`)',
            [
                'Web application',
                'Event-driven function',
            ],
            'Web application',
        );

        $templateDirectory = [
            'Web application' => 'http',
            'Event-driven function' => 'function',
        ][$choice];

        $this->rootPath = dirname(__DIR__, 3) . "/template/$templateDirectory";

        self::createFile('index.php');
        self::createFile('serverless.yml');

        $io->success('Project initialized and ready to test or deploy.');

        return 0;
    }

    /**
     * Creates files from the template directory and automatically adds
     * them to git
     */
    private function createFile(string $file): void
    {
        $overwrite = true;

        $this->io->writeln("Creating $file");

        if (file_exists($file)) {
            $overwrite = $this->io->confirm("A file named $file already exists, do you want to overwrite it?", true);
        }

        if ($overwrite) {
            $template = file_get_contents("$this->rootPath/$file");
            $template = str_replace('PHP_VERSION', PHP_MAJOR_VERSION . PHP_MINOR_VERSION, $template);
            file_put_contents($file, $template);

            /*
             * We check if this is a git repository to automatically add file to git.
             */
            $message = "$file successfully created";
            if ((new Process(['git', 'rev-parse', '--is-inside-work-tree']))->run() === 0) {
                (new Process(['git', 'add', $file]))->run();
                $message .= ' and added to git automatically';
            }

            $this->io->success("$message.");
        }
    }
}
