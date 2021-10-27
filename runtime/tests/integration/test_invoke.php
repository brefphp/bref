<?php declare(strict_types=1);

function post($url, array $params)
{
    $ch = curl_init();

    $jsonData = json_encode($params);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData),
    ]);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
}

function invoke($endpoint) {
    $response = post($endpoint, ['Hello' => 'From Bref!']);

    if ($response === false) {
        throw new RuntimeException("Failed to load response from [$endpoint]. Double check the Service DNS.");
    }

    $response = json_decode($response, true);

    if ($response['event']['Hello'] !== 'From Bref!') {
        throw new Exception('Unexpected response: ' . json_encode($response));
    }

    if ($response['memory_limit'] !== '586M') {
        throw new Exception('Failed to load php.ini from /var/task/php/conf.d/');
    }

    echo "\033[36m [Integration] Invocation response validated!\033[0m" . PHP_EOL;
}
