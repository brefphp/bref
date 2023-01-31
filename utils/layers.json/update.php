<?php declare(strict_types=1);

/**
 * This script updates `layers.json` at the root of the project.
 *
 * `layers.json` contains the layer versions that Bref should use.
 */

use AsyncAws\Lambda\LambdaClient;
use AsyncAws\Lambda\ValueObject\LayerVersionsListItem;
use Aws\Sts\StsClient; // AsyncAWS doesn't support regional endpoints: https://github.com/async-aws/aws/issues/1061

require_once __DIR__ . '/../../vendor/autoload.php';

const LAYER_NAMES = [
    'php-82',
    'php-82-fpm',
    'php-81',
    'php-81-fpm',
    'php-80',
    'php-80-fpm',
    'arm-php-82',
    'arm-php-82-fpm',
    'arm-php-81',
    'arm-php-81-fpm',
    'arm-php-80',
    'arm-php-80-fpm',
    'console',
];

$regions = json_decode(file_get_contents(__DIR__ . '/regions.json'), true);

$export = [];
foreach ($regions as $region) {
    $client = lambdaClient($region);

    $layers = listLayers($client, $region);

    foreach (LAYER_NAMES as $layerName) {
        $export[$layerName][$region] = $layers[$layerName] ?? '';
    }

    echo "$region\n";
}

file_put_contents(__DIR__ . '/../../layers.json', json_encode($export, JSON_PRETTY_PRINT));

echo "Done\n";

function lambdaClient(string $region): LambdaClient
{
    // If we're running on CodeBuild, let's switch to the Publisher Account Role.
    if (getenv('AWS_STS_REGIONAL_ENDPOINTS') === 'regional') {
        $stsClient = new StsClient([
            'sts_regional_endpoints' => 'regional',
            'region' => $region,
            'version' => '2011-06-15',
        ]);

        $credentials = $stsClient->AssumeRole([
            'RoleArn' => 'arn:aws:iam::534081306603:role/bref-layer-publisher',
            'RoleSessionName' => 'bref-layer-builder',
        ]);

        return new LambdaClient([
            'region' => $region,
            'accessKeyId' => $credentials['Credentials']['AccessKeyId'],
            'accessKeySecret' => $credentials['Credentials']['SecretAccessKey'],
            'sessionToken'  => $credentials['Credentials']['SessionToken'],
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

    foreach (LAYER_NAMES as $layerName) {
        $results[$layerName] = $lambda->listLayerVersions([
            'LayerName' => sprintf('arn:aws:lambda:%s:534081306603:layer:%s', $selectedRegion, $layerName),
            'MaxItems' => 1,
        ]);
    }

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
