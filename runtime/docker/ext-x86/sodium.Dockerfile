ARG PHP_VERSION

FROM bref/x86-${PHP_VERSION}-base as standard

ARG PHP_VERSION

RUN yum install -y ${PHP_VERSION}-php-sodium

FROM scratch

ARG PHP_VERSION

COPY --from=0 /usr/lib64/libsodium.so.23 /opt/lib/libsodium.so.23
COPY --from=0 /opt/remi/${PHP_VERSION}/root/lib64/php/modules/sodium.so /opt/php-modules/sodium.so
