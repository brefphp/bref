<?php
declare(strict_types=1);

namespace Bref\Console;

use Bref\Filesystem\DirectoryMirror;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;
use Matomo\Ini\IniReader;
use Matomo\Ini\IniWriter;
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

    /**
     * Invoke the function and return the output.
     *
     * @deprecated in favor of `php bref.php bref:invoke`, will be removed.
     */
    public function invoke(SymfonyStyle $io, string $function, ?string $data, bool $raw) : string
    {
        $progress = $this->createProgressBar($io, 7);

        $this->generateArchive($io, $progress);

        $progress->setMessage('Invoking the lambda');
        $progress->display();
        $progress->finish();

        $parameters = array_filter([
            '-f' => $function,
            '-d' => $data,
            '--raw' => $raw,
        ]);

        $p = join(' ', array_map(
            function ($value, $key) {
                if ($value === true) {
                    // Support for "flag" arguments
                    return $key;
                }
                return $key . ' ' . escapeshellarg($value);
            },
            $parameters,
            array_keys($parameters)
        ));

        $process = new Process('serverless invoke local ' . $p, '.bref/output');
        $process->setEnv([
            'BREF_LOCAL' => 'BREF_LOCAL',
        ]);
        $process->setTimeout(null);
        $process->mustRun();
        return $process->getOutput();
    }

    public function deploy(SymfonyStyle $io, bool $dryRun, ?string $stage) : void
    {
        $progress = $this->createProgressBar($io, 8);

        $this->generateArchive($io, $progress);

        if (!$dryRun) {
            $progress->setMessage('Uploading the lambda');
            $progress->display();
            $serverlessCommand = 'serverless deploy';
            if (null !== $stage) {
                $serverlessCommand .= ' --stage ' . escapeshellarg($stage);
            }
            $process = new Process($serverlessCommand, '.bref/output');
            $process->setTimeout(null);
            $completeDeployOutput = '';
            $process->mustRun(function ($type, $buffer) use ($io, $progress, &$completeDeployOutput) {
                $completeDeployOutput .= $buffer;
                $progress->clear();
                $io->writeln($buffer);
                $progress->display();
            });
        }

        $progress->setMessage('Deployment success');
        $progress->finish();

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
    private function generateArchive(SymfonyStyle $io, ProgressBar $progress) : void
    {
        if (!$this->fs->exists('serverless.yml') || !$this->fs->exists('bref.php')) {
            throw new \Exception('The files `bref.php` and `serverless.yml` are required to deploy, run `bref init` to create them');
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
            $projectConfig = Yaml::parse(file_get_contents('.bref.yml'));
        }
        $progress->advance();

        $progress->setMessage('Building the project in the `.bref/output` directory');
        $progress->display();
        $this->copyProjectToOutputDirectory();
        $progress->advance();

        // Cache PHP's binary in `.bref/bin/php` to avoid downloading it
        // on every deploy.
        $phpVersion = $projectConfig['php']['version'] ?? DEFAULT_PHP_TARGET_VERSION;

        $progress->setMessage('Downloading PHP in the `.bref/bin/` directory');
        $progress->display();
        if (!$this->fs->exists('.bref/bin/php/php-' . $phpVersion . '.tar.gz')) {
            $this->fs->mkdir('.bref/bin/php');
            /*
             * TODO This option allows to customize the PHP binary used. It should be documented
             * and probably moved to a dedicated option like:
             * php:
             *     url: 'https://s3.amazonaws.com/...'
             */
            $defaultUrl = 'https://s3.amazonaws.com/bref-php/bin/php-' . $phpVersion . '.tar.gz';
            $url = $projectConfig['php']['url'] ?? $defaultUrl;
            (new Process("curl -sSL $url -o .bref/bin/php/php-" . $phpVersion . ".tar.gz"))
                ->setTimeout(null)
                ->mustRun();
        }
        $progress->advance();

        $progress->setMessage('Installing the PHP binary');
        $progress->display();
        $this->fs->mkdir('.bref/output/.bref/bin');
        (new Process('tar -xzf .bref/bin/php/php-' . $phpVersion . '.tar.gz -C .bref/output/.bref/bin'))
            ->mustRun();
        // Set correct permissions on the file
        $this->fs->chmod('.bref/output/.bref/bin', 0755);
        // Install our custom php.ini and merge it with user configuration
        $phpConfig = $this->buildPhpConfig(
            __DIR__  . '/../../template/php.ini',
            '.bref/output/.bref/php.ini',
            $projectConfig['php']['configuration'] ?? [],
            $projectConfig['php']['extensions'] ?? []
        );
        // Remove unused extensions
        $this->removeUnusedExtensions($phpConfig);
        // Remove unused libraries
        $this->removeUnusedLibraries($projectConfig['php']['extensions'] ?? []);
        $progress->advance();

        $progress->setMessage('Installing Bref files for NodeJS');
        $progress->display();
        $this->copyServerlessYml();
        // Install `handler.js`
        $this->fs->copy(__DIR__ . '/../../template/handler.js', '.bref/output/handler.js');
        $progress->advance();

        $progress->setMessage('Installing composer dependencies');
        $progress->display();
        $this->runLocally('composer install --no-dev --classmap-authoritative --no-scripts');
        $progress->advance();

        // Run build hooks defined in .bref.yml
        $progress->setMessage('Running build hooks');
        $progress->display();
        $buildHooks = $projectConfig['hooks']['build'] ?? [];
        foreach ($buildHooks as $buildHook) {
            $progress->setMessage('Running build hook: ' . $buildHook);
            $progress->display();
            $this->runLocally($buildHook);
        }
        $progress->advance();
    }

    private function runLocally(string $command) : void
    {
        $process = new Process($command, '.bref/output');
        $process->setTimeout(null);
        $process->mustRun();
    }

    private function createProgressBar(SymfonyStyle $io, int $max) : ProgressBar
    {
        ProgressBar::setFormatDefinition('bref', "<comment>%message%</comment>\n %current%/%max% [%bar%] %elapsed:6s%");

        $progressBar = $io->createProgressBar($max);
        $progressBar->setFormat('bref');
        $progressBar->setBarCharacter('░');
        $progressBar->setEmptyBarCharacter(' ');
        $progressBar->setMessage('');

        $progressBar->start();

        return $progressBar;
    }

    /**
     * Pre-process the `serverless.yml` file and copy it in the lambda directory.
     */
    private function copyServerlessYml() : void
    {
        $serverlessYml = Yaml::parse(file_get_contents('serverless.yml'));

        // Force deploying the files used by Bref without having the user know about them
        $serverlessYml['package']['include'][] = 'handler.js';
        $serverlessYml['package']['include'][] = '.bref/**';

        file_put_contents('.bref/output/serverless.yml', Yaml::dump($serverlessYml, 10));
    }

    private function copyProjectToOutputDirectory() : void
    {
        if (!$this->fs->exists('.bref/output')) {
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

    private function buildPhpConfig(string $sourceFile, string $targetFile, array $flags, array $extensions): array
    {
        $config = array_merge(
            ['flags' => array_merge(
                (new IniReader())->readFile($sourceFile),
                $flags
            )],
            array_combine(
                $extensions,
                array_map(function ($extension) {
                    if (!$this->fs->exists('.bref/output/.bref/bin/ext/' . $extension . '.so')) {
                        throw new \Exception("The PHP extension '$extension' is not available yet in Bref, please open an issue or a pull request on GitHub to add that extension");
                    }

                    return ['extension' => $extension . '.so'];
                }, $extensions)
            )
        );

        (new IniWriter())->writeToFile($targetFile, $config);

        return $config;
    }

    private function removeUnusedExtensions(array $phpConfig)
    {
        foreach (glob('.bref/output/.bref/bin/ext/*.so') as $extensionFile) {
            if ($extensionFile === '.bref/output/.bref/bin/ext/opcache.so') {
                continue;
            }
            if (!array_key_exists(basename($extensionFile, '.so'), $phpConfig)) {
                $this->fs->remove($extensionFile);
            }
        }
    }

    private function removeUnusedLibraries(array $extensions)
    {
        $dependencies = [];
        $dependenciesFile = '.bref/output/.bref/bin/dependencies.yml';
        $librariesDir = '.bref/output/.bref/bin/lib/';

        if ($this->fs->exists($dependenciesFile)) {
            $dependencies = Yaml::parse(file_get_contents($dependenciesFile))['extensions'] ?? [];
            $this->fs->remove($dependenciesFile);
        }

        $requiredLibraries = array_unique(call_user_func_array(
            'array_merge',
            array_intersect_key($dependencies, array_flip($extensions)) + [[]]
        ));

        foreach (glob('.bref/output/.bref/bin/lib/**') as $library) {
            if (!in_array(basename($library), $requiredLibraries)) {
                $this->fs->remove($library);
            }
        }
    }
}
