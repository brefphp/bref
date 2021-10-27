ARG PHP_VERSION

FROM bref/x86-${PHP_VERSION}-base as standard

ARG PHP_VERSION

RUN yum install -y ${PHP_VERSION}-php-mbstring

FROM scratch

ARG PHP_VERSION

COPY --from=0 /usr/lib64/libonig.so.105 /opt/lib/libonig.so.105
COPY --from=0 /opt/remi/${PHP_VERSION}/root/lib64/php/modules/mbstring.so /opt/php-modules/mbstring.so
