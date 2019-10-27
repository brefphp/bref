ARG PHP_VERSION
FROM bref/php-$PHP_VERSION-fpm

# Override the config so that PHP-FPM listens on port 9000
COPY php-fpm.conf /opt/bref/etc/php-fpm.conf

EXPOSE 9000

# Clear the parent entrypoint
ENTRYPOINT []

# Run PHP-FPM
# opcache.validate_timestamps=1 : cancels the flag in the base configuration so that files are reloaded
CMD /opt/bin/php-fpm --nodaemonize --fpm-config /opt/bref/etc/php-fpm.conf -d opcache.validate_timestamps=1 --force-stderr
