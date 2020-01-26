<?php declare(strict_types=1);

header('Content-Type: application/json');

echo json_encode([
    '$_GET' => $_GET,
    '$_POST' => $_POST,
    '$_FILES' => array_map(function ($file) {
        // Fetch the content so we can verify it
        $file['content'] = file_get_contents($file['tmp_name']);
        // Delete the file and remove the random name as we can't assert that in the tests
        unlink($file['tmp_name']);
        unset($file['tmp_name']);
        return $file;
    }, $_FILES),
    '$_COOKIE' => $_COOKIE,
    '$_REQUEST' => $_REQUEST,
    '$_SERVER' => $_SERVER,
    'HTTP_RAW_BODY' => file_get_contents('php://input'),
], JSON_PRETTY_PRINT);
