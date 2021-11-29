FROM alpine:3.14

RUN apk add composer

# This composer.json file is a slim-down version of Bref to support Inline.
# We prevent installation from packages not necessary to make the layer
# work in order to keep bref/bref backward compatible.
COPY runtime/plugins/console/composer.json /opt/bref-internal-src/composer.json

# We then copy the entire bref package into the image so we can use composer
# Repositories to install the package and have it available on autoload.
COPY src/ /package/src

# Copy the root composer.json from Bref Package
COPY composer.json /package/composer.json

RUN composer update -d /opt/bref-internal-src/ --no-dev

FROM alpine:3.14 as zip-console

RUN apk add zip

COPY --from=0 /opt /opt

COPY runtime/plugins/console/bootstrap.sh /opt/bootstrap
COPY runtime/plugins/console/bootstrap.sh /var/runtime/bootstrap

WORKDIR /opt

RUN zip --quiet --recurse-paths /tmp/layer.zip .