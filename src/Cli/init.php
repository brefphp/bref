<?php declare(strict_types=1);

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

function init(?string $template): void
{
    $exeFinder = new ExecutableFinder;
    if (! $exeFinder->find('serverless')) {
        warning(
            'The `serverless` command is not installed.' . PHP_EOL .
            'You will not be able to deploy your application unless it is installed' . PHP_EOL .
            'Please follow the instructions at https://bref.sh/docs/installation.html' . PHP_EOL .
            'If you have the `serverless` command available elsewhere (eg in a Docker container) you can ignore this warning.' . PHP_EOL
        );
    }

    if (! $template) {
        $intro = green('What kind of application are you building?');
        echo <<<TEXT
    $intro (you will be able to add more functions later by editing `serverless.yml`)
      [0] Web application (default)
      [1] Event-driven functions
      [2] Symfony application

    TEXT;
        $choice = readline('> ') ?: '0';
        echo PHP_EOL;
        if (! in_array($choice, ['0', '1', '2'], true)) {
            error('Invalid response (must be "0", "1" or "2"), aborting');
        }

        $template = [
            '0' => 'http',
            '1' => 'function',
            '2' => 'symfony',
        ][$choice];
    }

    $rootPath = dirname(__DIR__, 2) . "/template/$template";

    if (file_exists($rootPath . '/index.php')) {
        createFile($rootPath, 'index.php');
    }
    createFile($rootPath, 'serverless.yml');

    // If these is a `.gitignore` file in the current directory, let's add `.serverless` to it
    if (file_exists('.gitignore')) {
        $gitignore = file_get_contents('.gitignore');
        if (! str_contains($gitignore, '.serverless')) {
            file_put_contents('.gitignore', PHP_EOL . '.serverless' . PHP_EOL, FILE_APPEND);
            success('Added `.serverless` to your `.gitignore` file.');
        }
    }

    success('Project initialized and ready to test or deploy.');
}

/**
 * Creates files from the template directory and automatically adds them to git
 */
function createFile(string $templatePath, string $file): void
{
    echo "Creating $file\n";

    if (file_exists($file)) {
        $overwrite = false;
        echo "A file named $file already exists, do you want to overwrite it? [y/N]\n";
        $choice = strtolower(readline('> ') ?: 'n');
        echo PHP_EOL;
        if ($choice === 'y') {
            $overwrite = true;
        } elseif (! in_array($choice, ['y', 'n'], true)) {
            error('Invalid response (must be "y" or "n"), aborting');
        }
        if (! $overwrite) {
            echo "Skipping $file\n";
            return;
        }
    }

    $template = file_get_contents("$templatePath/$file");
    if (! $template) {
        error("Could not read file $templatePath/$file");
    }
    $template = str_replace('PHP_VERSION', PHP_MAJOR_VERSION . PHP_MINOR_VERSION, $template);
    file_put_contents($file, $template);

    /*
     * We check if this is a git repository to automatically add file to git.
     */
    $message = "$file successfully created";
    if ((new Process(['git', 'rev-parse', '--is-inside-work-tree']))->run() === 0) {
        (new Process(['git', 'add', $file]))->run();
        $message .= ' and added to git automatically';
    }

    echo PHP_EOL;
    success("$message.");
}
