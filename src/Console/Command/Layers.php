<?php

declare(strict_types=1);

namespace Bref\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Layers extends Command
{
    protected static $defaultName = 'layers';
    protected static $defaultDescription = 'Displays the versions of the Bref layers';

    protected function configure(): void
    {
        $this->addArgument('region');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $region = $input->getArgument('region');

        $layers = json_decode(file_get_contents(__DIR__ . '/layers.json'), true);
        $io->title("Layers for the $region region");

        $array = [];
        foreach ($layers as $layer => $versions) {
            $version = $versions[$region];
            $array[] = [
                $layer,
                $version,
                "arn:aws:lambda:$region:416566615250:layer:$layer:$version",
            ];
        }
        $io->table([
            'Layer',
            'Version',
            'ARN',
        ], $array);

        return 0;
    }
}
