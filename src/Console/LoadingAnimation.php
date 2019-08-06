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
    /** @var int */
    private $lineLength = 0;

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
        // We store the line length to clear it properly later
        $this->lineLength = strlen($symbol) + 1 + strlen($message);

        $this->counter++;
    }

    /**
     * Clear the line.
     */
    public function clear(): void
    {
        // Move the cursor back at the beginning of the line
        $this->output->write("\r");
        // Erase the previously written characters (using spaces)
        $this->output->write(str_pad(' ', $this->lineLength));
        // Move the cursor back at the beginning of the line
        $this->output->write("\r");
    }
}
