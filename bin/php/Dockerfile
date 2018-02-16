# Inspired from https://github.com/araines/serverless-php by Andy Raines

# AWS Lambda uses this specific version
FROM amazonlinux:2017.03

ARG PHP_VERSION

# Lambda is based on 2017.03
# * dont' grab the latest revisions of development packages.
RUN yum --releasever=2017.03 install \
    autoconf \
    automake \
    libtool \
    bison \
    re2c \
    libxml2-devel \
    openssl-devel \
    libpng-devel \
    libjpeg-devel \
    curl-devel -y

RUN curl -sL https://github.com/php/php-src/archive/$PHP_VERSION.tar.gz | tar -zxv

WORKDIR /php-src-$PHP_VERSION

RUN ./buildconf --force

RUN ./configure \
    --enable-static=yes \
    --enable-shared=no \
    --disable-all \
    --enable-hash \
    --enable-json \
    --enable-libxml \
    --enable-mbstring \
    --enable-phar \
    --enable-soap \
    --enable-xml \
    --with-curl \
    --with-gd \
    --with-zlib \
    --with-openssl \
    --without-pear \
    --enable-ctype

RUN make -j 5
