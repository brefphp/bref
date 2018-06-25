#!/bin/sh
# Inspired from https://github.com/araines/serverless-php by Andy Raines
# This script builds a docker container, compiles PHP for use with AWS Lambda,
# and copies the final binary to the host and then removes the container.
#
# You can specify the PHP Version as the first argument.

set -e

# Set the current directory as this file's directory
cd "$(dirname "$0")"

if [[ $# -eq 0 ]] ; then
    echo 'The PHP version to build must be passed as the first argument'
    exit 1
fi

PHP_VERSION=$1

echo "Building PHP binary from tag 'php-$PHP_VERSION' on https://github.com/php/php-src"

# Compile PHP
docker build --build-arg PHP_VERSION=php-$PHP_VERSION -t php-build .

container=$(docker create php-build)

# Fetch the PHP binary in the container
docker -D cp $container:/php-src-php-$PHP_VERSION/sapi/cli/php .

# Fetch the extensions that were built too
mkdir -p ext
docker -D cp $container:/usr/local/lib/php/extensions/no-debug-non-zts-20170718/. ext/

docker rm $container

# Put all that in an archive
tar czf php-$PHP_VERSION.tar.gz php ext
rm php
rm -rf ext

# Upload the archive to AWS S3
aws s3 cp php-$PHP_VERSION.tar.gz s3://bref-php/bin/ --acl public-read
rm php-$PHP_VERSION.tar.gz
