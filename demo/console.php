<?php declare(strict_types=1);

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

require __DIR__.'/../vendor/autoload.php';

$app = new Application();

$app->register('hello')
    ->addArgument('name', InputArgument::OPTIONAL, '', 'World!')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        $output->writeln('Hello, ' . $input->getArgument('name'));
        return Command::SUCCESS;
    })
;

$app->register('error')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        $output->writeln('There was an error!');
        return Command::FAILURE;
    })
;

$app->register('sleep')
    ->setCode(function (InputInterface $input, OutputInterface $output): int {
        sleep(120);
        return Command::SUCCESS;
    })
;

$app->run();
