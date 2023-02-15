<?php declare(strict_types=1);

error_log('This is a log message');

$timeout = (int) ($_GET['timeout'] ?? 10);
$result = sleep($timeout);

if ($result && $timeout > 0) {
    throw new Exception('The execution continued after sleep was interrupted!');
}
