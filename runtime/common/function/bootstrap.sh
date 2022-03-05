#!/bin/sh

# Fail on error
set -e

# Every bootstrap file will be executing src/Toolbox/bootstrap.php and passing an argument
# of which type of bootstrapping we're doing. Possible values are function|fpm|console.

# We don't compile PHP anymore, so the only way to configure where PHP should be looking for
# .ini files is via the PHP_INI_SCAN_DIR environment variable.
export PHP_INI_SCAN_DIR="/opt/php-ini/:/var/task/php/conf.d/"

while true
do
    # We redirect stderr to stdout so that everything
    # written on the output ends up in Cloudwatch automatically
    /opt/bin/php "/opt/bref-internal-src/vendor/bref/bref/src/Toolbox/bootstrap.php" "function" 2>&1
done
