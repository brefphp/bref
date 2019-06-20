<?php declare(strict_types=1);

/**
 * This script updates `layers.json` at the root of the project.
 *
 * `layers.json` contains the layer versions that Bref should use.
 */

use Aws\Lambda\LambdaClient;
use function GuzzleHttp\Promise\unwrap;

require_once __DIR__ . '/../vendor/autoload.php';

const LAYER_NAMES = [
    'php-73',
    'php-73-fpm',
    'php-72',
    'php-72-fpm',
    'console',
];

$regions = json_decode(file_get_contents(__DIR__ . '/regions.json'), true);

$export = [];
foreach ($regions as $region) {
    $layers = listLayers($region);
    foreach (LAYER_NAMES as $layerName) {
        $export[$layerName][$region] = $layers[$layerName];
    }
    echo "$region\n";
}
file_put_contents(__DIR__ . '/../layers.json', json_encode($export, JSON_PRETTY_PRINT));
echo "Done\n";


function listLayers(string $selectedRegion): array
{
    $lambda = new LambdaClient([
        'version' => 'latest',
        'region' => $selectedRegion,
    ]);

    // Run the API calls in parallel (thanks to async)
    $promises = array_combine(LAYER_NAMES, array_map(function (string $layerName) use ($lambda, $selectedRegion) {
        return $lambda->listLayerVersionsAsync([
            'LayerName' => "arn:aws:lambda:$selectedRegion:209497400698:layer:$layerName",
            'MaxItems' => 1,
        ]);
    }, LAYER_NAMES));

    // Wait on all of the requests to complete. Throws a ConnectException
    // if any of the requests fail
    $results = unwrap($promises);

    $layers = [];
    foreach ($results as $layerName => $result) {
        $versions = $result['LayerVersions'];
        $latestVersion = end($versions);
        $layers[$layerName] = $latestVersion['Version'];
    }

    return $layers;
}

