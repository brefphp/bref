<?php declare(strict_types=1);

echo 'first';
// Send it now, even with output buffering enabled in php.ini
while (ob_get_level() > 0) {
    ob_end_flush();
}
flush();

// Wait for the test to receive the first chunk
$deadline = microtime(true) + 3;
while (! file_exists($_GET['file']) && microtime(true) < $deadline) {
    usleep(1000);
}

echo 'second';
