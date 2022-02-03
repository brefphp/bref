<?php declare(strict_types=1);

namespace Bref\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class Layers extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('layers')
            ->setDescription('Displays the versions of the Bref layers')
            ->addArgument('region', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $region = $input->getArgument('region');

        $layersJson = file_get_contents(dirname(__DIR__, 3) . '/layers.json');
        $layers = json_decode($layersJson, true, 512, JSON_THROW_ON_ERROR);
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
