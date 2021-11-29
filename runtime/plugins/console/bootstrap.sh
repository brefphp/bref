#!/bin/sh

# Fail on error
set -e

export PHP_INI_SCAN_DIR="/opt/php-ini/:/var/task/php/conf.d/"

/opt/bin/php "/opt/bref-internal-src/vendor/bref/bref/src/Toolbox/bootstrap.php" "console" 2>&1
