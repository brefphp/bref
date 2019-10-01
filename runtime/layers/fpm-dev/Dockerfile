ARG LAYER_IMAGE
FROM $LAYER_IMAGE

COPY php-fpm.conf /opt/bref/etc/php-fpm.conf
COPY php.ini /opt/bref/etc/php/conf.d/bref.ini

# Build the final image from the lambci image that is close to the production environment
FROM lambci/lambda:provided

COPY --from=0  /opt /opt

EXPOSE 9000
EXPOSE 9001

# Clear the parent entrypoint
ENTRYPOINT []

# Run PHP-FPM
# opcache.validate_timestamps=1 : cancels the flag in the base configuration so that files are reloaded
CMD /opt/bin/php-fpm --nodaemonize --fpm-config /opt/bref/etc/php-fpm.conf -d opcache.validate_timestamps=1 --force-stderr
