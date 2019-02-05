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

# Create the PHP FPM self contained layer
# Add files specific to this layer
cp /layers/fpm-self/bootstrap bootstrap
chmod 755 bootstrap
cp /layers/fpm-self/php.ini bref/etc/php/conf.d/bref.ini
cp /layers/fpm-self/php-fpm.conf bref/etc/php-fpm.conf
cp /layers/fpm-self/bref/composer.json bref/composer.json
cp -r /src bref/src
curl https://getcomposer.org/download/1.8.3/composer.phar -o composer.phar -s
cd bref
php ../composer.phar install -o --no-dev
cd ..
# Zip the layer
zip --quiet --recurse-paths /export/php-${PHP_SHORT_VERSION}-fpm-self.zip . --exclude "*php-cgi"
# Cleanup the files specific to this layer
rm bootstrap
rm composer.phar
rm bref/composer.json
rm -rf bref/vendor
rm -rf bref/src
rm bref/etc/php/conf.d/bref.ini
rm bref/etc/php-fpm.conf
