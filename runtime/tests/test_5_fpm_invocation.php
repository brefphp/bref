<?php declare(strict_types=1);

function post(string $url, array $params)
{
    $ch = curl_init();

    $jsonData = json_encode($params);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Content-Length: ' . strlen($jsonData)]);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);

    curl_close($ch);

    return $response;
}

$body = [
    'BREF_HTTP_METHOD' => 'GET',
];

$response = post('http://127.0.0.1:8080/2015-03-31/functions/function/invocations', $body);

$response = json_decode($response, true);

if ($response['body'] !== 'Hello from Bref FPM!') {
    throw new Exception('Unexpected response: ' . json_encode($response));
}

echo "\033[36m [Invoke] FPM ✓!\033[0m" . PHP_EOL;
