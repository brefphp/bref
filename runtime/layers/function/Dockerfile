ARG LAYER_IMAGE
FROM $LAYER_IMAGE

WORKDIR /opt

COPY bootstrap bootstrap
COPY php.ini bref/etc/php/conf.d/bref.ini

# Remove PHP-FPM
RUN rm bref/sbin/php-fpm bin/php-fpm

# Build the final image from the lambci image that is close to the production environment
FROM lambci/lambda:provided

COPY --from=0  /opt /opt
