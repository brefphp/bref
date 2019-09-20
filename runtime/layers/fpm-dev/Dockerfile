ARG LAYER_IMAGE
FROM $LAYER_IMAGE

COPY ./php-fpm.conf /opt/bref/etc/php-fpm.conf

# Build the final image from the lambci image that is close to the production environment
FROM lambci/lambda:provided

COPY --from=0  /opt /opt

EXPOSE 9000
CMD /opt/bin/php-fpm --nodaemonize --fpm-config /opt/bref/etc/php-fpm.conf
