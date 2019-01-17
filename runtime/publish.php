<?php declare(strict_types=1);

/**
 * This script publishes all the layers in all the regions.
 */

use Symfony\Component\Process\Process;

require_once __DIR__ . '/../vendor/autoload.php';

$layers = [
    'php-72' => 'PHP 7.2 for PHP functions',
    'php-72-fpm' => 'PHP-FPM 7.2 for HTTP applications',
    'console' => 'Console runtime for PHP applications',
];
foreach ($layers as $layer => $layerDescription) {
    $file = __DIR__ . "/export/$layer.zip";
    if (! file_exists($file)) {
        echo "File $file does not exist: generate the archives first\n";
        exit(1);
    }
}

/**
 * These are the regions on which the layers are published.
 */
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
    'ap-south-1',
    'ap-northeast-1',
    'ap-northeast-2',
    'ap-southeast-1',
    'ap-southeast-2',
];

foreach ($regions as $region) {
    foreach ($layers as $layer => $layerDescription) {
        $file = __DIR__ . "/export/$layer.zip";

        $publishLayer = new Process([
            'aws',
            'lambda',
            'publish-layer-version',
            '--region',
            $region,
            '--layer-name',
            $layer,
            '--description',
            $layerDescription,
            '--license-info',
            'MIT',
            '--zip-file',
            'fileb://' . $file,
            '--compatible-runtimes',
            'provided',
            // Output the version so that we can fetch it and use it
            '--output',
            'text',
            '--query',
            'Version',
        ]);
        $publishLayer->setTimeout(null);
        $publishLayer->mustRun();
        $layerVersion = trim($publishLayer->getOutput());

        $addPermissions = new Process([
            'aws',
            'lambda',
            'add-layer-version-permission',
            '--region',
            $region,
            '--layer-name',
            $layer,
            '--version-number',
            $layerVersion,
            '--statement-id',
            'public',
            '--action',
            'lambda:GetLayerVersion',
            '--principal',
            '*',
        ]);
        $addPermissions->setTimeout(null);
        $addPermissions->mustRun();

        echo "Published $layer in $region\n";
    }
}
