<?php declare(strict_types=1);

namespace Bref\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class Init extends Command
{
    protected function configure(): void
    {
        $this->setName('init');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $helper = $this->getHelper('question');

        $filesToGitAdd = [];
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

        $fs = new Filesystem;

        $createFile = static function ($file) use ($templateDirectory, $io, $helper, $input, $output, &$filesToGitAdd): void {
            $write = true;
            $rootPath = dirname(__DIR__, 3) . "/template/$templateDirectory";
            $io->writeln("Creating $file");
            if (file_exists($file)) {
                $question = new ConfirmationQuestion(
                    "An $file file already exists, do you want to overwrite it? [Y/n]",
                    false,
                    '/^(y|j)/i'
                );

                $write = $helper->ask($input, $output, $question);
            }

            if ($write) {
                $template = file_get_contents("$rootPath/$file");
                $template = str_replace('PHP_VERSION', PHP_MAJOR_VERSION . PHP_MINOR_VERSION, $template);
                file_put_contents($file, $template);
                $filesToGitAdd[] = $file;
            }
        };

        $createFile('index.php');
        $createFile('serverless.yml');

        /*
         * We check if this is a git repository to automatically add files to git.
         */
        if ((new Process(['git', 'rev-parse', '--is-inside-work-tree']))->run() === 0 && $filesToGitAdd) {
            $files = implode(',', $filesToGitAdd);
            foreach ($filesToGitAdd as $file) {
                (new Process(['git', 'add', $file]))->run();
            }

            if ($filesToGitAdd !== []) {
                $io->success([
                    'Project initialized and ready to test or deploy.',
                    "The file/s $files created automatically added to git.",
                ]);
            }
            return 0;
        }

        $io->success('Project initialized and ready to test or deploy.');

        return 0;
    }
}
