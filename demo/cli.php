<?php
declare(strict_types=1);

use Silly\Application;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Debug\Debug;

require __DIR__.'/vendor/autoload.php';

Debug::enable();

// CLI application using Silly
$silly = new Application;
$silly->command('hello [name]', function (string $name = 'World!', OutputInterface $output) {
    $output->writeln('Hello ' . $name);
});
$silly->command('phpinfo', function (OutputInterface $output) {
    ob_start();
    phpinfo();
    $phpinfo = ob_get_contents();
    ob_clean();
    $output->write($phpinfo);
});

$app = new \Bref\Application;
$app->simpleHandler(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
$app->cliHandler($silly);
$app->run();
