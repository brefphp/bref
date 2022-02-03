<?php declare(strict_types=1);

namespace Bref\Console\Command;

use Bref\Lambda\InvocationFailed;
use Bref\Lambda\SimpleLambdaClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Cli extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('cli')
            ->setDescription('Runs a CLI command in the remote environment')
            ->addArgument('function', InputArgument::REQUIRED)
            ->addArgument('arguments', InputArgument::IS_ARRAY)
            ->addOption('region', 'r', InputOption::VALUE_REQUIRED)
            ->addOption('profile', 'p', InputOption::VALUE_REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $function = $input->getArgument('function');
        $arguments = $input->getArgument('arguments');
        $region = $input->getOption('region');
        $profile = $input->getOption('profile');

        $lambda = new SimpleLambdaClient(
            $region ?: getenv('AWS_DEFAULT_REGION') ?: 'us-east-1',
            $profile ?: getenv('AWS_PROFILE') ?: 'default',
            15 * 60 // maximum duration on Lambda
        );

        // Because arguments may contain spaces, and are going to be executed remotely
        // as a separate process, we need to escape all arguments.
        $arguments = array_map(static function (string $arg): string {
            return escapeshellarg($arg);
        }, $arguments);

        try {
            $result = $lambda->invoke($function, json_encode(implode(' ', $arguments)));
        } catch (InvocationFailed $e) {
            $io->getErrorStyle()->writeln('<info>' . $e->getInvocationLogs() . '</info>');
            $io->error($e->getMessage());
            return 1;
        }

        $payload = $result->getPayload();
        if (isset($payload['output'])) {
            $io->writeln($payload['output']);
        } else {
            $io->error('The command did not return a valid response.');
            $io->writeln('<info>Logs:</info>');
            $io->write('<comment>' . $result->getLogs() . '</comment>');
            $io->writeln('<info>Lambda result payload:</info>');
            $io->writeln(json_encode($payload, JSON_PRETTY_PRINT));
            return 1;
        }

        return (int) ($payload['exitCode'] ?? 1);
    }
}
