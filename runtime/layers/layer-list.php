<?php declare(strict_types=1);

/**
 * This script updates `layers.json` at the root of the project.
 *
 * `layers.json` contains the layer versions that Bref should use.
 */

use AsyncAws\Lambda\LambdaClient;
use AsyncAws\Core\Sts\StsClient;
use AsyncAws\Lambda\ValueObject\LayerVersionsListItem;

require_once __DIR__ . '/../../vendor/autoload.php';

const LAYER_NAMES = [
    'php-80',
    'php-80-fpm',
    'php-74',
    'php-74-fpm',
    'php-73',
    'php-73-fpm',
    'console',
];

//$regions = json_decode(file_get_contents(__DIR__ . '/regions.json'), true);

$export = [];
//foreach ($regions as $region) {
    $region = 'eu-west-1';

    $client = lambdaClient($region);

    $layers = listLayers($client, $region);
    foreach (LAYER_NAMES as $layerName) {
        $export[$layerName][$region] = $layers[$layerName];
    }
    echo "$region\n";
//}
file_put_contents(__DIR__ . '/../../layers.json', json_encode($export, JSON_PRETTY_PRINT));
echo "Done\n";


function lambdaClient(string $region): LambdaClient
{
    if (getenv('AWS_STS_REGIONAL_ENDPOINTS') === 'regional') {
        $stsClient = new StsClient([
            'region' => $region,
            'version' => '2011-06-15'
        ]);

        $credentials = $stsClient->AssumeRole([
            'RoleArn' => 'arn:aws:iam::179453031647:role/bref-layer-publisher',
            'RoleSessionName' => 'bref-layer-builder',
        ]);

        return new LambdaClient([
            'region' => $region,
            'credentials' => [
                'key'    => $credentials['Credentials']['AccessKeyId'],
                'secret' => $credentials['Credentials']['SecretAccessKey'],
                'token'  => $credentials['Credentials']['SessionToken']
            ],
        ]);
    } else {
        return new LambdaClient([
            'region' => $region,
        ]);
    }
}

function listLayers(LambdaClient $lambda, string $selectedRegion): array
{
    // Run the API calls in parallel (thanks to async)
    $results = [];
//    foreach (LAYER_NAMES as $layerName) {
        $layerName = 'GoogleSheetLayers';

        $results[$layerName] = $lambda->listLayerVersions([
            'LayerName' => sprintf('arn:aws:lambda:%s:179453031647:layer:%s', $selectedRegion, $layerName),
            'MaxItems' => 1,
        ]);
//    }

    $layers = [];
    foreach ($results as $layerName => $result) {
        $versions = $result->getLayerVersions(true);
        $versionsArray = iterator_to_array($versions);
        if (! empty($versionsArray)) {
            /** @var LayerVersionsListItem $latestVersion */
            $latestVersion = end($versionsArray);
            $layers[$layerName] = $latestVersion->getVersion();
        }
    }

    return $layers;
}
