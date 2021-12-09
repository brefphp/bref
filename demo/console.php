<?php declare(strict_types=1);

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

require __DIR__.'/../vendor/autoload.php';

$app = new Application();

$app->add(new class('hello') extends Command {
    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::OPTIONAL, '', 'World!');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Hello, ' . $input->getArgument('name'));
        return 0;
    }
});

$app->add((new class('phpinfo') extends Command {})->setCode(
    function (InputInterface $input, OutputInterface $output) {
        ob_start();
        phpinfo();
        $phpinfo = ob_get_clean();
        $output->write($phpinfo);
        return 0;
    }
));

$app->add((new class('error') extends Command {})->setCode(
    function (InputInterface $input, OutputInterface $output) {
        $output->writeln('There was an error!');
        return 1;
    }
));

$app->add((new class('sleep') extends Command {})->setCode(
    function (InputInterface $input, OutputInterface $output) {
        sleep(120);
        return 0;
    }
));

$app->run();
