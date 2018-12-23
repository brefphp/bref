<?php declare(strict_types=1);

namespace Bref\Console;

use Bref\Filesystem\DirectoryMirror;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class Deployer
{
    /** @var Filesystem */
    private $fs;

    public function __construct()
    {
        $this->fs = new Filesystem;
    }

    public function deploy(SymfonyStyle $io, bool $dryRun): void
    {
        $progress = $this->createProgressBar($io, 8);

        $this->generateArchive($progress);

        if (! $dryRun) {
            $progress->setMessage('Uploading the lambda');
            $progress->display();
            $command = ['serverless', 'deploy'];
            $process = new Process($command, '.bref/output');
            $process->setTimeout(null);
            $completeDeployOutput = '';
            $process->mustRun(function ($type, $buffer) use ($io, $progress, &$completeDeployOutput): void {
                $completeDeployOutput .= $buffer;
                $progress->clear();
                $io->writeln($buffer);
                $progress->display();
            });
        }

        $progress->setMessage('Deployment success');
        $progress->finish();

        // Finish the output on a new line
        $io->newLine();

        // Trigger a desktop notification
        $notifier = NotifierFactory::create();
        $notification = (new Notification)
            ->setTitle('Deployment success')
            ->setBody('Bref has deployed your application');
        $notifier->send($notification);
    }

    /**
     * @param ProgressBar $progress The progress bar will advance of 7 steps.
     * @throws \Exception
     */
    private function generateArchive(ProgressBar $progress): void
    {
        if (! $this->fs->exists('template.yaml')) {
            throw new \Exception('The file `template.yaml` is required to deploy, run `bref init` to create it');
        }

        // Parse .bref.yml
        $projectConfig = [];
        if ($this->fs->exists('.bref.yml')) {
            $progress->setMessage('Reading `.bref.yml`');
            $progress->display();
            /*
             * TODO validate the content of the config, for example we should
             * error if there are unknown keys. Using the Symfony Config component
             * for that could make sense.
             */
            $projectConfig = Yaml::parse($this->readContent('.bref.yml'));
        }

        $progress->setMessage('Building the project in the `.bref/output` directory');
        $progress->display();
        $this->copyProjectToOutputDirectory();
        $progress->advance();

        $progress->setMessage('Installing composer dependencies');
        $progress->display();
        $process = new Process(['composer', 'install', '--no-dev', '--classmap-authoritative', '--no-scripts'], '.bref/output');
        $process->setTimeout(null);
        $process->mustRun();
        $progress->advance();

        // Run build hooks defined in .bref.yml
        $progress->setMessage('Running build hooks');
        $progress->display();
        $buildHooks = $projectConfig['hooks']['build'] ?? [];
        foreach ($buildHooks as $buildHook) {
            $progress->setMessage('Running build hook: ' . $buildHook);
            $progress->display();
            $process = new Process([], '.bref/output'); // replace with `fromShellCommandline()` when supporting Symfony ^4.2
            $process->setCommandLine($buildHook);
            $process->setTimeout(null);
            $process->mustRun();
        }
        $progress->advance();
    }

    private function createProgressBar(SymfonyStyle $io, int $max): ProgressBar
    {
        ProgressBar::setFormatDefinition('bref', "<comment>%message%</comment>\n %current%/%max% [%bar%] %elapsed:6s%");

        $progressBar = $io->createProgressBar($max);
        $progressBar->setFormat('bref');
        $progressBar->setMessage('');

        $progressBar->start();

        return $progressBar;
    }

    private function copyProjectToOutputDirectory(): void
    {
        if (! $this->fs->exists('.bref/output')) {
            $this->fs->mkdir('.bref/output');
        }

        $source = (new Finder)
            ->in('.')
            ->exclude('.bref') // avoid a recursive copy
            ->exclude('vendor') // vendors are installed with Composer so we don't need to copy them
            ->exclude('.idea')
            ->ignoreVCS(true)
            ->ignoreDotFiles(false);

        $target = (new Finder)
            ->in('.bref/output')
            ->exclude('vendor') // vendors are installed with Composer so we don't need to copy them
            ->ignoreVCS(false)
            ->ignoreDotFiles(false);

        $directoryMirror = new DirectoryMirror($this->fs);
        $directoryMirror->mirror($source, $target);
    }

    private function readContent(string $file): string
    {
        $content = file_get_contents($file);
        if ($content === false) {
            throw new \RuntimeException("Unable to read the `$file` file");
        }

        return $content;
    }
}
