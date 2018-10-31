<?php
declare(strict_types=1);

namespace Bref\Cli;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A Symfony Console command to invoke a lambda locally.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class InvokeCommand extends Command
{
    /**
     * @var callable
     */
    private $invokerLocator;

    public function __construct(callable $invokerLocator)
    {
        $this->invokerLocator = $invokerLocator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('bref:invoke')
            ->setDescription('Invoke the lambda locally when testing it in a development environment.')
            ->setHelp('This command does NOT run the lambda on a serverless provider. It can be used to test the lambda in a "direct invocation" mode on a development machine.')
            ->addOption('event', 'e', InputOption::VALUE_REQUIRED, 'Event data as JSON')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Event data as file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $simpleHandler = ($this->invokerLocator)();

        $event = [];

        if ($option = $input->getOption('event')) {
            if (null === $event = json_decode($option, true)) {
                throw new \RuntimeException('The `--event` option provided contains invalid JSON: ' . $option);
            }
        }

        if ($option = $input->getOption('path')) {
            if (!$path = realpath($option)) {
                throw new \RuntimeException('The `--path` option is an invalid path: ' . $option);
            }
            if (!is_readable($path)) {
                throw new \RuntimeException('The `--path` option reference an invalid file path or misses permission: ' . $option);
            }
            if (!$fileContent = file_get_contents($path)) {
                throw new \RuntimeException('Unable to get file content:' . $option);
            }
            if (null === $event = json_decode($fileContent, true)) {
                throw new \RuntimeException('The `--path` option provided an file with invalid JSON content: ' . $option);
            }
        }

        $payload = $simpleHandler($event);

        $output->writeln(json_encode($payload, JSON_PRETTY_PRINT));
    }
}
