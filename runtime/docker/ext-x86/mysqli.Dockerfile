ARG PHP_VERSION

FROM bref/x86-${PHP_VERSION}-base as standard

ARG PHP_VERSION

RUN yum install -y ${PHP_VERSION}-php-mysqli ${PHP_VERSION}-php-mysqlnd

FROM scratch

ARG PHP_VERSION

# mysqli depends on mysqlnd
COPY --from=0 /opt/remi/${PHP_VERSION}/root/lib64/php/modules/mysqli.so /opt/php-modules/mysqli.so
COPY --from=0 /opt/remi/${PHP_VERSION}/root/lib64/php/modules/mysqlnd.so /opt/php-modules/mysqlnd.so