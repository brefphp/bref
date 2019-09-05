ARG LAYER_IMAGE
FROM $LAYER_IMAGE

COPY bootstrap /opt/bootstrap
COPY php.ini /opt/bref/etc/php/conf.d/bref.ini
COPY php-fpm.conf /opt/bref/etc/php-fpm.conf

RUN if [ "$LAYER_IMAGE" = "php-73-fpm" ] ||  [ "$LAYER_IMAGE" = "php-73" ]; \
    then echo "decorate_workers_output not supported"; \
    else echo "decorate_workers_output = no" >> /opt/bref/etc/php-fpm.conf; fi

# Build the final image from the lambci image that is close to the production environment
FROM lambci/lambda:provided

COPY --from=0  /opt /opt
