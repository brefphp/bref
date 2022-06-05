ARG PHP_VERSION
FROM bref/build-php-$PHP_VERSION as build_extensions
ARG PHP_VERSION

RUN pecl install swoole
RUN cp $(php -r "echo ini_get('extension_dir');")/swoole.so /tmp

ARG PHP_VERSION
FROM bref/tmp/cleaned-build-php-$PHP_VERSION
ARG PHP_VERSION

# Copy Swoole Extension
COPY --from=build_extensions /tmp/swoole.so /tmp/swoole.so
RUN cp /tmp/swoole.so $(php -r "echo ini_get('extension_dir');")

COPY bootstrap /opt/bootstrap
COPY breftoolbox.php /opt/breftoolbox.php
COPY php.ini /opt/bref/etc/php/conf.d/bref.ini

# Build the final image from the amazon image that is close to the production environment
FROM public.ecr.aws/lambda/provided:al2
COPY --from=1 /opt /opt
# Copy files to /var/runtime to support deploying as a Docker image
RUN cp /opt/bootstrap /var/runtime && cp /opt/breftoolbox.php /var/runtime
