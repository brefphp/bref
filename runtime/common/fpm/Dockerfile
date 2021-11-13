FROM alpine:3.14

RUN apk add composer

# This composer.json file is a slim-down version of Bref to support Inline.
# We prevent installation from packages not necessary to make the layer
# work in order to keep bref/bref backward compatible.
COPY runtime/common/fpm/composer.json /opt/bref-internal-src/composer.json

# We can copy PHP FPM config here and it will be copied together with the entire
# source code folder. This is referenced in Bref\Event\Http\FpmHandler::class
COPY runtime/common/fpm/php-fpm.conf /opt/bref-internal-src/php-fpm.conf

# We then copy the entire bref package into the image so we can use composer
# Repositories to install the package and have it available on autoload.
COPY src/ /package/src

# Copy the root composer.json from Bref Package
COPY composer.json /package/composer.json

RUN composer update -d /opt/bref-internal-src/ --no-dev