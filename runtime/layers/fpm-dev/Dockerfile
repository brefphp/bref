ARG LAYER_IMAGE
FROM $LAYER_IMAGE

# Override the config so that PHP-FPM listens on port 9000
COPY php-fpm.conf /opt/bref/etc/php-fpm.conf

USER root
ENV current_os=linux
RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;")-zts \
    && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/$current_os/amd64/$version \
    && mkdir -p /tmp/blackfire \
    && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
    && cp /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get('extension_dir');")/blackfire.so \
    && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz

# Build the final image from the lambci image that is close to the production environment
FROM lambci/lambda:provided

COPY --from=0  /opt /opt

EXPOSE 9000

# Clear the parent entrypoint
ENTRYPOINT []

# Run PHP-FPM
# opcache.validate_timestamps=1 : cancels the flag in the base configuration so that files are reloaded
CMD /opt/bin/php-fpm --nodaemonize --fpm-config /opt/bref/etc/php-fpm.conf -d opcache.validate_timestamps=1 --force-stderr
