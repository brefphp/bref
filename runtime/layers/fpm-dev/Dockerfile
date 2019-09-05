ARG LAYER_IMAGE
FROM $LAYER_IMAGE

COPY ./php-fpm.conf /opt/bref/etc/php-fpm.conf

RUN if [ "$LAYER_IMAGE" = "php-73-fpm" ] ||  [ "$LAYER_IMAGE" = "php-73" ]; \
    then echo "decorate_workers_output not supported"; \
    else echo "decorate_workers_output = no" >> /opt/bref/etc/php-fpm.conf; fi

EXPOSE 9000
CMD /opt/bin/php-fpm --nodaemonize --fpm-config /opt/bref/etc/php-fpm.conf
