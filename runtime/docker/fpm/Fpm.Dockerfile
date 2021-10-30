FROM alpine:3.14

RUN apk add composer

# This composer.json file is a slim-down version of Bref to support FPM.
# We prevent installation from packages not necessary to make the layer
# work in order to keep bref/bref backward compatible.
COPY runtime/docker/fpm/composer.json /opt/bref-fpm-src/composer.json

# We then copy the entire bref package into the image so we can use composer
# Repositories to install the package and have it available on autoload.
COPY src/ /package/src

COPY composer.json /package/composer.json

RUN composer update -d /opt/bref-fpm-src/ --no-dev