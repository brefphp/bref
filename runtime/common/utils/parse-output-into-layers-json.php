<?php declare(strict_types=1);

# This file is part of the `make everything` process.
# When publishing all layers, the publish.sh script will write the /tmp/bref-zip/output.ini file
# containing all regions, layers and the version. An ini file was used because it is lock-safe.
# (Imagine trying to write a JSON file in parallel and getting the content mixed up)
# with an ini file we can always append to the end of the file safely and without needing to
# lock the file and delay execution. After all layers have been published we can open up
# the ini file and parse it into a JSON.

$output = parse_ini_file('/tmp/bref-zip/output.ini');

$cpu = $_SERVER['argv'][1];

if ($cpu !== 'x86' && $cpu !== 'arm64') {
    throw new Exception("[$cpu] is unexpected. Possible values are [x86] and [arm64]");
}

# Let's create a backward-compatible mapping with the previous layer naming convention.
if ($cpu === 'x86') {
    foreach ($output as $layer => $regionVersionCollection) {
        if (str_ends_with($layer, 'function')) {
            // Here we'll parse layers such as x86-php80-function and extract php80 into $matches
            preg_match('/php\d\d/', $layer, $matches);

            // Now that we have the php version, we can toss out the `php` string and keep the version number.
            $version = str_replace('php', '', $matches[0]);

            // Let's copy the entire layer versions under this backward compatible name
            $output["php-$version"] = $regionVersionCollection;
        }
    }
}

file_put_contents("/tmp/bref-zip/layers.$cpu.json", json_encode($output, JSON_PRETTY_PRINT));