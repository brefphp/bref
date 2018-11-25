<?php declare(strict_types=1);

namespace Bref\Cli;

use Innmind\Json\Exception\RuntimeException;
use Innmind\Json\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * A Symfony Console command to invoke a lambda locally.
 */
class InvokeCommand extends Command
{
    /** @var callable */
    private $invokerLocator;

    public function __construct(callable $invokerLocator)
    {
        $this->invokerLocator = $invokerLocator;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('bref:invoke')
            ->setDescription('Invoke the lambda locally when testing it in a development environment.')
            ->setHelp('This command does NOT run the lambda on a serverless provider. It can be used to test the lambda in a "direct invocation" mode on a development machine.')
            ->addOption('event', 'e', InputOption::VALUE_REQUIRED, 'Event data as JSON')
            ->addOption('path', 'p', InputOption::VALUE_REQUIRED, 'Event data as file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $simpleHandler = ($this->invokerLocator)();

        $event = [];

        $eventOption = $input->getOption('event');
        if ($eventOption) {
            $eventOption = (string) $eventOption;
            try {
                $event = Json::decode($eventOption);
            } catch (RuntimeException $e) {
                throw new \RuntimeException("The `--event` option provided contains invalid JSON: $eventOption", 0, $e);
            }
        }

        $pathOption = $input->getOption('path');
        if ($pathOption) {
            $event = $this->readPathOption($pathOption);
        }

        $payload = $simpleHandler($event);

        $output->writeln(json_encode($payload, JSON_PRETTY_PRINT));
    }

    /**
     * @return mixed
     */
    private function readPathOption(string $option)
    {
        $path = realpath($option);
        if (! $path) {
            throw new \RuntimeException('The `--path` option is an invalid path: ' . $option);
        }

        if (! is_readable($path)) {
            throw new \RuntimeException('The `--path` option reference an invalid file path or misses permission: ' . $option);
        }

        $fileContent = file_get_contents($path);
        if (! $fileContent) {
            throw new \RuntimeException('Unable to get file content:' . $option);
        }

        $event = json_decode($fileContent, true);
        if ($event === null) {
            throw new \RuntimeException('The `--path` option provided an file with invalid JSON content: ' . $option);
        }

        return $event;
    }
}
