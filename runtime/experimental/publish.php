<?php declare(strict_types=1);

/**
 * This script publishes all the layers in all the regions.
 */

use Symfony\Component\Process\Process;

require_once __DIR__ . '/../../vendor/autoload.php';

$layers = [
    'php-7.2',
    'php-7.2-fpm',
];

$regions = [
    'ca-central-1',
    'eu-central-1',
    'eu-west-1',
    'eu-west-2',
    'eu-west-3',
    'sa-east-1',
    'us-east-1',
    'us-east-2',
    'us-west-1',
    'us-west-2',
];

foreach ($regions as $region) {
    foreach ($layers as $layer) {
        $file = __DIR__ . "/export/$layer.zip";

        if (! file_exists($file)) {
            echo "File $file does not exist: generate the archives first\n";
            exit(1);
        }

        $process = new Process([__DIR__ . '/helpers/publish.sh']);
        $process->setEnv([
            'REGION' => $region,
            'LAYER_NAME' => $layer,
            'FILE_NAME' => $file,
        ]);
        $process->mustRun();

        echo "Published $layer in $region\n";
    }
}
