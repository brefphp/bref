<?php

function handler($event, $context){
    print('Event: ' . $event . PHP_EOL);
    print('Context: ' . $context . PHP_EOL);
    print('Current PHP version: ' . phpversion() . PHP_EOL);
    $response = [
        "statusCode" => 200,
        "headers" => [
            "x-custom-header" => "my custom header value"
            ],
        "body" => [
            "test" => "success"
            ],
        ];

    return $response;
}

