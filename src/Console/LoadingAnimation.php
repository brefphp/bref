<?php declare(strict_types=1);

namespace Bref\Console;

use Symfony\Component\Console\Output\OutputInterface;

class LoadingAnimation
{
    private const CHARACTERS = ['⠇', '⠋', '⠙', '⠸', '⠴', '⠦'];

    /** @var int */
    private $counter = 0;
    /** @var OutputInterface */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * Update the animation to the next character.
     */
    public function tick(string $message): void
    {
        $i = $this->counter % count(self::CHARACTERS);
        $symbol = self::CHARACTERS[$i];

        $this->clear();
        $this->output->write("<fg=red>$symbol</> $message");

        $this->counter++;
    }

    /**
     * Clear the line.
     */
    public function clear(): void
    {
        // Write a character that removes the current line
        $this->output->write("\r");
    }
}
