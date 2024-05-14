<?php declare(strict_types=1);

echo "Hello world!\n";

if (isset($argv[1]) && $argv[1] === 'fail') {
    echo "Failure\n";
    exit(1);
}
