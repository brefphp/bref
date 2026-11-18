<?php declare(strict_types=1);

if (isset($argv[1]) && $argv[1] === 'flood') {
    // Print 7MB of data to go over the 6MB limit
    echo str_repeat('x', 7 * 1024 * 1024);
    exit(0);
}

if (isset($argv[1]) && $argv[1] === 'flood-multibyte') {
    // Over the 6MB limit, with 3-byte characters: trimming the output cuts one in half
    echo str_repeat('€', intdiv(7 * 1024 * 1024, 3));
    exit(0);
}

if (isset($argv[1]) && $argv[1] === 'invalid-utf8') {
    echo "Invalid UTF-8: \xB1\x31\n";
    exit(0);
}

echo "Hello world!\n";

if (isset($argv[1]) && $argv[1] === 'fail') {
    echo "Failure\n";
    exit(1);
}
