<?php declare(strict_types=1);

# This file is only used manually.
# The goal is to reduce layer size by not copying any file that is not strictly necessary.
# We do this by executing one Lambda with the following `/opt/bootstrap` file:

##########
/**
#!/bin/sh
ls /lib64 -la
*/
##########

# The result of the Lambda execution should then be copied into the al2-x64.txt file.
# We will then read the Dockerfile, remove all comments and compare any file that we
# may be copying into the layer that doesn't need to be there.

$docker = file_get_contents(__DIR__ . '/../../../php74/cpu-x86.Dockerfile');

$dockerContent = explode(PHP_EOL, $docker);

$dockerContent = array_filter($dockerContent, fn ($item) => ! str_starts_with($item, '#') && ! empty($item));

$docker = implode(PHP_EOL, $dockerContent);

$content = file_get_contents(__DIR__ . '/al2-x64.txt');

$libraries = explode(PHP_EOL, $content);

foreach ($libraries as $library) {
    if (! str_contains($library, '.so')) {
        continue;
    }

    if (str_contains($docker, $library)) {
        echo "[$library] is present in Docker but is also present on /lib64 by default" . PHP_EOL;
    }
}
