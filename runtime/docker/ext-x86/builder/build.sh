#!/bin/bash

PHP_VERSION=php81
EXTENSION=opcache

docker build \
  --build-arg PHP_VERSION=${PHP_VERSION} \
  --build-arg EXTENSION=${EXTENSION} \
  -f ext-builder.Dockerfile \
  . -t bref/ext-builder

docker run \
  --entrypoint /usr/bin/ldd \
  bref/ext-builder \
  /opt/remi/${PHP_VERSION}/root/lib64/php/modules/${EXTENSION}.so