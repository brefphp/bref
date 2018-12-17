#!/bin/sh
# This script is run inside a Lambda container to compile PHP for Lambda's OS.
# It will export a ZIP usable as a Lambda Layer.
# See https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html#configuration-layers-path
#
# You must specify the PHP version using the `PHP_VERSION` environment variable.

set -e

if [[ -z "$PHP_VERSION" ]] ; then
    echo 'The PHP version to build must be passed in the PHP_VERSION environment variable'
    exit 1
fi

# Install dependencies
# --releasever=2017.03: Lambda is based on 2017.03, dont' grab the latest revisions of development packages.
# findutils: because the pecl command uses find
# libicu and libicu-devel: because C++ & ICU are required by INTL
yum -y --releasever=2017.03 install \
    autoconf \
    automake \
    libtool \
    bison \
    re2c \
    libxml2-devel \
    openssl-devel \
    libpng-devel \
    libjpeg-devel \
    curl-devel \
    findutils \
    php-pear \
    libicu \
    libicu-devel \
    c++ \
    gcc-c++

# Compile PHP
mkdir /tmp/bref
cd /tmp/bref
curl -sL https://github.com/php/php-src/archive/php-$PHP_VERSION.tar.gz | tar -zxv
cd php-src-php-$PHP_VERSION
./buildconf --force
# --enable-option-checking=fatal: make sure invalid --configure-flags are fatal errors instead of just warnings
# --enable-ftp: because ftp_ssl_connect() needs ftp to be compiled statically (see https://github.com/docker-library/php/issues/236)
# --enable-mbstring: because otherwise there's no way to get pecl to use it properly (see https://github.com/docker-library/php/issues/195)
# --enable-opcache-file: allows to use the `opcache.file_cache` option
# --with-mhash: https://github.com/docker-library/php/issues/439
./configure \
    --enable-option-checking=fatal \
    --enable-static=yes \
    --enable-shared=no \
    --with-config-file-path=/opt \
    --disable-cgi \
    --disable-fpm \
    --disable-phpdbg \
    --enable-ftp \
    --enable-mbstring \
    --enable-mysqlnd \
    --enable-pcntl \
    --enable-opcache \
    --enable-opcache-file \
    --enable-soap \
    --enable-zip \
    --with-curl \
    --with-openssl \
    --with-zlib \
    --with-gd \
    --with-pdo-mysql \
    --with-mhash > /dev/null
make -j 4 > /dev/null
make install

# Install extensions through pecl
pecl install mongodb redis > /dev/null

# Compile the intl extension because it cannot be installed with pecl
cd /tmp/bref/php-src-php-$PHP_VERSION/ext/intl
phpize > /dev/null
./configure > /dev/null
make > /dev/null
make install

# Copy the generated binary into /opt/bin (this directory is automatically included in the $PATH)
# See https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html#configuration-layers-path
mkdir /opt/bin
cp /tmp/bref/php-src-php-$PHP_VERSION/sapi/cli/php /opt/bin/
# Copy the generated extensions into /opt/php/extensions (included in php.ini)
mkdir -p /opt/php/extensions
cp /usr/local/lib/php/extensions/no-debug-non-zts-20170718/* /opt/php/extensions/
# Copy the system libraries into /opt/lib (this directory is automatically included by the lambda)
# See https://docs.aws.amazon.com/lambda/latest/dg/configuration-layers.html#configuration-layers-path
mkdir /opt/lib
cp /usr/lib64/libicui18n.so.50.1.2 /opt/lib/libicui18n.so.50
cp /usr/lib64/libicuuc.so.50.1.2 /opt/lib/libicuuc.so.50
cp /usr/lib64/libicudata.so.50.1.2 /opt/lib/libicudata.so.50
cp /usr/lib64/libicuio.so.50.1.2 /opt/lib/libicuio.so.50

cp /export/bootstrap /opt/
cp /export/php.ini /opt/

# Package /opt into a zip and make the zip available to the host
cd /opt
zip -r /export/php.zip ./*

ls -la /opt
