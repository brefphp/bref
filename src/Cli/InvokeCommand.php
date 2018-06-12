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
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $simpleHandler = ($this->invokerLocator)();

        $event = [];
        if ($input->getOption('event')) {
            $event = json_decode($input->getOption('event'), true);
            if ($event === null) {
                throw new \RuntimeException('The `--event` option provided contains invalid JSON: ' . $input->getOption('event'));
            }
        }

        $payload = $simpleHandler($event);

        $output->writeln(json_encode($payload, JSON_PRETTY_PRINT));
    }
}
