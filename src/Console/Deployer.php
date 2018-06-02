<?php
declare(strict_types=1);

namespace Bref\Console;

use Bref\Util\CommandRunner;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

class Deployer
{
    /** @var CommandRunner */
    private $commandRunner;

    /** @var Filesystem */
    private $fs;

    public function __construct()
    {
        $this->commandRunner = new CommandRunner;
        $this->fs = new Filesystem;
    }

    public function invoke(SymfonyStyle $io, string $function, ?string $path, ?string $data, ?string $raw, ?string $contextPath, ?string $context)
    {
        $this->init($io);
        $parameters = array_filter([
            '-f' => $function,
            '-p' => $path,
            '-d' => $data,
            '-raw' => $raw,
            '-x' => $contextPath,
            '-c' => $context,
        ]);

        $p = join(' ', array_map(
            function ($value, $key) {
                return $key . ' \'' . $value . '\'';
            },
            $parameters,
            array_keys($parameters)
        ));

        return $this->commandRunner->run('cd .bref/output && serverless invoke local' . $p);
    }

    public function deploy(SymfonyStyle $io)
    {
        $this->init($io);

        $io->writeln('Uploading the lambda');
        $this->commandRunner->run('cd .bref/output && serverless deploy');

        // Trigger a desktop notification
        $notifier = NotifierFactory::create();
        $notification = (new Notification)
            ->setTitle('Deployment success')
            ->setBody('Bref has deployed your application');
        $notifier->send($notification);
    }

    protected function init(SymfonyStyle $io)
    {
        if (!$this->fs->exists('serverless.yml') || !$this->fs->exists('bref.php')) {
            throw new \Exception('The files `bref.php` and `serverless.yml` are required to deploy, run `bref init` to create them');
        }

        // Parse .bref.yml
        $projectConfig = [];
        if ($this->fs->exists('.bref.yml')) {
            /*
             * TODO validate the content of the config, for example we should
             * error if there are unknown keys. Using the Symfony Config component
             * for that could make sense.
             */
            $projectConfig = Yaml::parse(file_get_contents('.bref.yml'));
        }

        $io->writeln('Building the project in the `.bref/output` directory');
        /*
         * TODO Mirror the directory instead of recreating it from scratch every time
         * Blocked by https://github.com/symfony/symfony/pull/26399
         * In the meantime we destroy `.bref/output` completely every time which
         * is not efficient.
         */
        $this->fs->remove('.bref/output');
        $this->fs->mkdir('.bref/output');
        $filesToCopy = new Finder;
        $filesToCopy->in('.')
            ->depth(0)
            ->exclude('.bref')// avoid a recursive copy
            ->ignoreDotFiles(false);
        foreach ($filesToCopy as $fileToCopy) {
            if (is_file($fileToCopy->getPathname())) {
                $this->fs->copy($fileToCopy->getPathname(), '.bref/output/' . $fileToCopy->getFilename());
            } else {
                $this->fs->mirror($fileToCopy->getPathname(), '.bref/output/' . $fileToCopy->getFilename(), null, [
                    'copy_on_windows' => true, // Force to copy symlink content
                ]);
            }
        }

        // Cache PHP's binary in `.bref/bin/php` to avoid downloading it
        // on every deploy.
        /*
         * TODO Allow choosing a PHP version instead of using directly the
         * constant `PHP_TARGET_VERSION`. That could be done using the `.bref.yml`
         * config file: there could be an option in that config, for example:
         * php:
         *     version: 7.2.2
         */
        if (!$this->fs->exists('.bref/bin/php/php-' . PHP_TARGET_VERSION . '.tar.gz')) {
            $io->writeln('Downloading PHP in the `.bref/bin/` directory');
            $this->fs->mkdir('.bref/bin/php');
            $defaultUrl = 'https://s3.amazonaws.com/bref-php/bin/php-' . PHP_TARGET_VERSION . '.tar.gz';
            /*
             * TODO This option allows to customize the PHP binary used. It should be documented
             * and probably moved to a dedicated option like:
             * php:
             *     url: 'https://s3.amazonaws.com/...'
             */
            $url = $projectConfig['php'] ?? $defaultUrl;
            $this->commandRunner->run("curl -sSL $url -o .bref/bin/php/php-" . PHP_TARGET_VERSION . ".tar.gz");
        }

        $io->writeln('Installing the PHP binary');
        $this->fs->mkdir('.bref/output/.bref/bin');
        $this->commandRunner->run('tar -xzf .bref/bin/php/php-' . PHP_TARGET_VERSION . '.tar.gz -C .bref/output/.bref/bin');

        $io->writeln('Installing `handler.js`');
        $this->fs->copy(__DIR__ . '/template/handler.js', '.bref/output/handler.js');

        $io->writeln('Installing composer dependencies');
        $this->commandRunner->run('cd .bref/output && composer install --no-dev --classmap-authoritative --no-scripts');

        /*
         * TODO Edit the `serverless.yml` copy (in `.bref/output` to deploy these files:
         * - bref.php
         * - handler.js
         * - .bref/**
         */

        // Run build hooks defined in .bref.yml
        $buildHooks = $projectConfig['hooks']['build'] ?? [];
        foreach ($buildHooks as $buildHook) {
            $io->writeln('Running ' . $buildHook);
            $this->commandRunner->run('cd .bref/output && ' . $buildHook);
        }
    }
}
