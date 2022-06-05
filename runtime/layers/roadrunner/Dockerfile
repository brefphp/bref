ARG PHP_VERSION
FROM bref/tmp/cleaned-build-php-$PHP_VERSION
ARG PHP_VERSION

COPY bootstrap /opt/bootstrap
COPY breftoolbox.php /opt/breftoolbox.php
COPY php.ini /opt/bref/etc/php/conf.d/bref.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN cd /tmp && \
    composer require spiral/roadrunner:v2.0 && \
    ./vendor/bin/rr get-binary

# Build the final image from the amazon image that is close to the production environment
FROM public.ecr.aws/lambda/provided:al2
COPY --from=0 /opt /opt
# Copy Roadrunner Binary
COPY --from=0 /tmp/rr /opt/bin/rr
# Ensure Execute Permission
RUN chmod +x /opt/bin/rr

# Copy files to /var/runtime to support deploying as a Docker image
RUN cp /opt/bootstrap /var/runtime && cp /opt/breftoolbox.php /var/runtime
