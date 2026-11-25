<?php declare(strict_types=1);

echo json_encode([
    'default_socket_timeout' => ini_get('default_socket_timeout'),
    'mysqlnd.net_read_timeout' => ini_get('mysqlnd.net_read_timeout'),
]);
