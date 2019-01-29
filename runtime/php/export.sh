#!/bin/bash

# Fail on error
set -e
# Fail on undefined variables
set -u
# Print all commands before executing them
set -x

cd /opt

# Create the PHP CLI layer
cp /layers/function/bootstrap bootstrap
chmod 755 bootstrap
cp /layers/function/php.ini bref/etc/php/conf.d/bref.ini
# Zip the layer
zip --quiet --recurse-paths /export/php-${PHP_SHORT_VERSION}.zip . --exclude "*php-cgi"
# Remove PHP-FPM from this layer
zip --delete /export/php-${PHP_SHORT_VERSION}.zip bref/sbin/php-fpm bin/php-fpm
# Cleanup the files specific to this layer
rm bootstrap
rm bref/etc/php/conf.d/bref.ini

# Create the PHP FPM layer
# Add files specific to this layer
cp /layers/fpm/bootstrap bootstrap
chmod 755 bootstrap
cp /layers/fpm/php.ini bref/etc/php/conf.d/bref.ini
cp /layers/fpm/php-fpm.conf bref/etc/php-fpm.conf
# Zip the layer
zip --quiet --recurse-paths /export/php-${PHP_SHORT_VERSION}-fpm.zip . --exclude "*php-cgi"
# Cleanup the files specific to this layer
rm bootstrap
rm bref/etc/php/conf.d/bref.ini
rm bref/etc/php-fpm.conf
