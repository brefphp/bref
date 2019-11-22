ARG PHP_VERSION
FROM bref/tmp/cleaned-build-php-$PHP_VERSION

COPY bootstrap /opt/bootstrap
COPY php.ini /opt/bref/etc/php/conf.d/bref.ini
COPY php-fpm.conf /opt/bref/etc/php-fpm.conf

# Build the final image from the lambci image that is close to the production environment
FROM lambci/lambda:provided
COPY --from=0  /opt /opt
