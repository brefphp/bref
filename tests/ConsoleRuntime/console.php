<?php declare(strict_types=1);

if (isset($argv[1]) && $argv[1] === 'flood') {
    // Print 7MB of data to go over the 6MB limit
    echo str_repeat('x', 7 * 1024 * 1024);
    exit(0);
}

echo "Hello world!\n";

if (isset($argv[1]) && $argv[1] === 'fail') {
    echo "Failure\n";
    exit(1);
}
