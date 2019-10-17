ARG LAYER_IMAGE
FROM $LAYER_IMAGE

COPY extensions-test.sh /var/task/extensions-test.sh
ARG ini=smoke.php.ini
COPY $ini /var/task/php/conf.d/php.ini

ENTRYPOINT /var/task/extensions-test.sh
CMD
