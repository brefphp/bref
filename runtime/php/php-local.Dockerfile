ARG LAYER_IMAGE=bref/php-73:latest
FROM $LAYER_IMAGE

WORKDIR /opt/bin
EXPOSE 9000

COPY ./local/php-fpm.conf /opt/bref/etc/php-fpm.conf

CMD [ "php-fpm", "-F", "-y", "/opt/bref/etc/php-fpm.conf" ]
