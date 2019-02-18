<?php declare(strict_types=1);

/**
 * This script publishes all the layers in all the regions.
 */

use Symfony\Component\Process\Process;

require_once __DIR__ . '/../vendor/autoload.php';

$layers = [
    'php-73' => 'PHP 7.3 for PHP functions',
    'php-73-fpm' => 'PHP-FPM 7.3 for HTTP applications',
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
    'eu-north-1',
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

// Publish the layers
/** @var Process[] $publishingProcesses */
$publishingProcesses = [];
foreach ($regions as $region) {
    foreach ($layers as $layer => $layerDescription) {
        $publishingProcesses[$region . $layer] = publishLayer($region, $layer, $layerDescription);
    }
}
runProcessesInParallel($publishingProcesses);
echo "\nAll layers are published, adding permissions now\n";

// Add public permissions on the layers
/** @var Process[] $permissionProcesses */
$permissionProcesses = [];
foreach ($regions as $region) {
    foreach ($layers as $layer => $layerDescription) {
        $publishLayer = $publishingProcesses[$region . $layer];
        $layerVersion = trim($publishLayer->getOutput());

        $permissionProcesses[] = addPublicLayerPermissions($region, $layer, $layerVersion);
    }
}
runProcessesInParallel($permissionProcesses);
echo "\nDone\n";

function publishLayer(string $region, string $layer, string $layerDescription): Process
{
    $file = __DIR__ . "/export/$layer.zip";

    $process = new Process([
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
    $process->setTimeout(null);

    return $process;
}

/**
 * @param Process[] $processes
 */
function runProcessesInParallel(array $processes): void
{
    // Run the processes in batches to parallelize them without overloading the machine and the network
    foreach (array_chunk($processes, 4) as $batch) {
        // Start all the processes
        array_map(function (Process $process): void {
            $process->start();
        }, $batch);
        // Wait for them to finish
        array_map(function (Process $process): void {
            $status = $process->wait();
            echo '.';
            // Make sure the process ran successfully
            if ($status !== 0) {
                echo 'Process ' . $process->getCommandLine() . ' failed:' . PHP_EOL;
                echo $process->getErrorOutput();
                echo $process->getOutput();
                exit(1);
            }
        }, $batch);
    }
}

function addPublicLayerPermissions(string $region, string $layer, string $layerVersion): Process
{
    $process = new Process([
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
    $process->setTimeout(null);

    return $process;
}
