<?php declare(strict_types=1);

namespace Bref\Console\Command;

use Bref\Console\LoadingAnimation;
use Bref\Console\OpenUrl;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class Dashboard extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('dashboard')
            ->setDescription('Starts the dashboard')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, '', 'localhost')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, '', '8000')
            ->addOption('profile', null, InputOption::VALUE_REQUIRED)
            ->addOption('stage', null, InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $host = $input->getOption('host');
        $port = (int) $input->getOption('port');
        $profile = $input->getOption('profile');
        $stage = $input->getOption('stage');

        $io->info('The Bref Dashboard is also available as an application: https://dashboard.bref.sh');
        if ($host === 'localhost') {
            $host = '127.0.0.1';
        }
        if ($profile === null) {
            $profile = getenv('AWS_PROFILE') ?: 'default';
        }

        if (! file_exists('serverless.yml')) {
            $io->error('No `serverless.yml` file found.');

            return 1;
        }

        $exeFinder = new ExecutableFinder;
        if (! $exeFinder->find('docker')) {
            $io->error(
                'The `docker` command is not installed.' . PHP_EOL .
                'Please follow the instructions at https://docs.docker.com/install/'
            );

            return 1;
        }

        if (! $exeFinder->find('serverless')) {
            $io->error(
                'The `serverless` command is not installed.' . PHP_EOL .
                'Please follow the instructions at https://bref.sh/docs/installation.html'
            );

            return 1;
        }

        $args = ['serverless', 'info', '--aws-profile', $profile];
        if ($stage) {
            $args[] = '--stage';
            $args[] = $stage;
        }
        $serverlessInfo = new Process($args);
        $serverlessInfo->start();
        $animation = new LoadingAnimation($io);
        do {
            $animation->tick('Retrieving the stack');
            usleep(100 * 1000);
        } while ($serverlessInfo->isRunning());
        $animation->clear();

        if (! $serverlessInfo->isSuccessful()) {
            $io->error('The command `serverless info` failed' . PHP_EOL . $serverlessInfo->getOutput());

            return 1;
        }

        $serverlessInfoOutput = $serverlessInfo->getOutput();

        $region = [];
        preg_match('/region: ([a-z0-9-]*)/', $serverlessInfoOutput, $region);
        $region = $region[1];

        $stack = [];
        preg_match('/stack: ([a-zA-Z0-9-]*)/', $serverlessInfoOutput, $stack);
        $stack = $stack[1];

        $io->writeln("Stack: <fg=yellow>$stack ($region)</>");

        $dockerPull = new Process(['docker', 'pull', 'bref/dashboard']);
        $dockerPull->setTimeout(null);
        $dockerPull->start();
        do {
            $animation->tick('Retrieving the latest version of the dashboard');
            usleep(100 * 1000);
        } while ($dockerPull->isRunning());
        $animation->clear();
        if (! $dockerPull->isSuccessful()) {
            $io->error([
                'The command `docker pull bref/dashboard` failed',
                $dockerPull->getErrorOutput(),
            ]);

            return 1;
        }

        $process = new Process(['docker', 'run', '--rm', '-p', $host . ':' . $port . ':8000', '-v', getenv('HOME') . '/.aws:/root/.aws:ro', '--env', 'STACKNAME=' . $stack, '--env', 'REGION=' . $region, '--env', 'AWS_PROFILE=' . $profile, 'bref/dashboard']);
        $process->setTimeout(null);
        $process->start();
        do {
            $animation->tick('Starting the dashboard');
            usleep(100 * 1000);
            $serverOutput = $process->getOutput() . $process->getErrorOutput();
            $hasStarted = (strpos($serverOutput, 'Development Server') !== false);
        } while ($process->isRunning() && ! $hasStarted);
        $animation->clear();
        if (! $process->isRunning()) {
            $io->error([
                'The dashboard failed to start',
                $process->getErrorOutput(),
            ]);

            return 1;
        }
        $url = "http://$host:$port";
        $io->writeln("Dashboard started: <fg=green;options=bold,underscore>$url</>");
        OpenUrl::open($url);
        $process->wait(function ($type, $buffer): void {
            if ($type === Process::ERR) {
                echo 'ERR > ' . $buffer;
            } else {
                echo 'OUT > ' . $buffer;
            }
        });

        return $process->getExitCode();
    }
}
