#!/bin/bash

# Fail on error
set -e
# Fail on undefined variables
set -u
# Print all commands before executing them
set -x

export PHP_ZIP_NAME='/export/php-72'

cd /opt

# We do not support running pear functions in Lambda
rm -rf bref/lib/php/PEAR
rm -rf bref/share/doc
rm -rf bref/share/man
rm -rf bref/share/gtk-doc
rm -rf bref/include
rm -rf bref/tests
rm -rf bref/doc
rm -rf bref/docs
rm -rf bref/man
rm -rf bref/www
rm -rf bref/cfg
rm -rf bref/libexec
rm -rf bref/var
rm -rf bref/data

# Create the PHP CLI layer
cp /layers/function/bootstrap bootstrap
chmod 755 bootstrap
cp /layers/function/php.ini bref/etc/php/php.ini
# Zip the layer
zip --quiet --recurse-paths ${PHP_ZIP_NAME}.zip . --exclude "*php-cgi"
# Remove PHP-FPM from this layer
zip --delete ${PHP_ZIP_NAME}.zip bref/sbin/php-fpm bin/php-fpm
# Cleanup the files specific to this layer
rm bootstrap
rm bref/etc/php/php.ini

# Create the PHP FPM layer
# Add files specific to this layer
cp /layers/fpm/bootstrap bootstrap
chmod 755 bootstrap
cp /layers/fpm/php.ini bref/etc/php/php.ini
cp /layers/fpm/php-fpm.conf bref/etc/php-fpm.conf
# Zip the layer
zip --quiet --recurse-paths ${PHP_ZIP_NAME}-fpm.zip . --exclude "*php-cgi"
# Cleanup the files specific to this layer
rm bootstrap
rm bref/etc/php/php.ini
rm bref/etc/php-fpm.conf
