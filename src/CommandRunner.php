<?php
declare(strict_types=1);

namespace PhpLambda;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class CommandRunner
{
    /**
     * Runs a command.
     *
     * @param string $command The command to execute.
     * @return string Output of the command.
     * @throws \Exception If the command failed.
     */
    public function run(string $command) : string
    {
        exec($command . ' 2>&1', $output, $returnValue);
        $output = trim(implode(PHP_EOL, $output));
        if ($returnValue !== 0) {
            throw new \Exception($output);
        }

        return $output;
    }

    public function runInDirectory(string $directory, string $command) : string
    {
        return $this->run("cd \"$directory\" && $command");
    }
}
