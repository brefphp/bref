<?php
declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

$app = new \PhpLambda\Application();

$app->run(function (array $event) {
    return [
        'hello' => $event['name'] ?? 'world',
    ];
});
