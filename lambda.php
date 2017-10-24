<?php
declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = new \PhpLambda\Application();

$app->run(function (array $event, \PhpLambda\Context $context, \PhpLambda\IO $io) {
    $name = $event['name'];

    $io->write("Hello $name");
});
