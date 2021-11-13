#!/opt/bin/php
<?php declare(strict_types=1);

require '/opt/bref-internal-src/vendor/autoload.php';

$initializer = $_SERVER['argv'][1];

$environment = [
    // AWS Lambda
    '_HANDLER' => getenv('_HANDLER'),
    'LAMBDA_TASK_ROOT' => getenv('LAMBDA_TASK_ROOT'),
    'AWS_LAMBDA_RUNTIME_API' => (string) getenv('AWS_LAMBDA_RUNTIME_API'),

    // Bref
    'BREF_DOWNLOAD_VENDOR' => getenv('BREF_DOWNLOAD_VENDOR'),
    'BREF_AUTOLOAD_PATH' => getenv('BREF_AUTOLOAD_PATH'),
    'BREF_LOOP_MAX' => getenv('BREF_LOOP_MAX'),

    // AWS Authentication
    'AWS_REGION' => getenv('AWS_REGION'),
    'AWS_ACCESS_KEY_ID' => getenv('AWS_ACCESS_KEY_ID'),
    'AWS_SECRET_ACCESS_KEY' => getenv('AWS_SECRET_ACCESS_KEY'),
    'AWS_SESSION_TOKEN' => getenv('AWS_SESSION_TOKEN'),
];

$runner = new \Bref\Toolbox\Runner($initializer, $environment);

$runner->run();
