ARG PHP_VERSION

FROM bref/arm64-${PHP_VERSION}-base

ARG PHP_VERSION

RUN yum install -y \
    php-mbstring \
    php-bcmath \
    php-dom \
    php-mysqli \
    php-mysqlnd \
    php-opcache \
    php-pdo \
    php-pdo_mysql \
    php-phar \
    php-posix \
    php-simplexml \
    php-xml \
    php-xmlreader \
    php-xmlwriter

FROM scratch

COPY --from=0 /usr/lib64/php/modules/mbstring.so /opt/php-modules/mbstring.so
COPY --from=0 /usr/lib64/libonig.so.105 /opt/lib/libonig.so.105

# mysqli depends on mysqlnd
COPY --from=0 /usr/lib64/php/modules/mysqli.so /opt/php-modules/mysqli.so
COPY --from=0 /usr/lib64/php/modules/mysqlnd.so /opt/php-modules/mysqlnd.so

COPY --from=0 /usr/lib64/libsqlite3.so.0 /opt/lib/libsqlite3.so.0
COPY --from=0 /usr/lib64/php/modules/sqlite3.so /opt/php-modules/sqlite3.so

COPY --from=0 /usr/lib64/php/modules/bcmath.so /opt/php-modules/bcmath.so
COPY --from=0 /usr/lib64/php/modules/dom.so /opt/php-modules/dom.so
COPY --from=0 /usr/lib64/php/modules/opcache.so /opt/php-modules/opcache.so
COPY --from=0 /usr/lib64/php/modules/pdo.so /opt/php-modules/pdo.so
COPY --from=0 /usr/lib64/php/modules/pdo_mysql.so /opt/php-modules/pdo_mysql.so
COPY --from=0 /usr/lib64/php/modules/pdo_sqlite.so /opt/php-modules/pdo_sqlite.so
COPY --from=0 /usr/lib64/php/modules/phar.so /opt/php-modules/phar.so
COPY --from=0 /usr/lib64/php/modules/posix.so /opt/php-modules/posix.so
COPY --from=0 /usr/lib64/php/modules/simplexml.so /opt/php-modules/simplexml.so
COPY --from=0 /usr/lib64/php/modules/xml.so /opt/php-modules/xml.so
COPY --from=0 /usr/lib64/php/modules/xmlreader.so /opt/php-modules/xmlreader.so
COPY --from=0 /usr/lib64/php/modules/xmlwriter.so /opt/php-modules/xmlwriter.so
