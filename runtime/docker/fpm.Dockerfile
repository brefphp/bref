ARG AWS_TAG

ARG ARCHITECTURE

ARG PHP_VERSION

FROM bref/${ARCHITECTURE}-${PHP_VERSION}-base as base
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-bcmath as bcmath
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-dom as dom
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-mbstring as mbstring
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-mysqli as mysqli
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-opcache as opcache
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-pdo as pdo
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-pdo_mysql as pdo_mysql
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-simplexml as simplexml

FROM base as fpm

ARG PHP_VERSION

RUN yum install -y ${PHP_VERSION}-php-fpm ${PHP_VERSION}-php-posix

RUN /bin/cat /opt/remi/${PHP_VERSION}/root/sbin/php-fpm > /bref/bin/php-fpm

RUN chmod +x /bref/bin/php-fpm

RUN /bin/cat /opt/remi/${PHP_VERSION}/root/lib64/php/modules/posix.so > /bref/php-modules/posix.so

RUN /bin/cat /lib64/libacl.so.1 > /bref/lib/libacl.so.1
RUN /bin/cat /lib64/libsystemd.so.0 > /bref/lib/libsystemd.so.0
RUN /bin/cat /lib64/libattr.so.1 > /bref/lib/libattr.so.1
RUN /bin/cat /lib64/libcap.so.2 > /bref/lib/libcap.so.2
RUN /bin/cat /lib64/liblz4.so.1 > /bref/lib/liblz4.so.1
RUN /bin/cat /lib64/libgcrypt.so.11 > /bref/lib/libgcrypt.so.11
RUN /bin/cat /lib64/libgpg-error.so.0 > /bref/lib/libgpg-error.so.0
RUN /bin/cat /lib64/libdw.so.1 > /bref/lib/libdw.so.1
RUN /bin/cat /lib64/libelf.so.1 > /bref/lib/libelf.so.1
RUN /bin/cat /lib64/libbz2.so.1 > /bref/lib/libbz2.so.1

FROM public.ecr.aws/lambda/provided:al2-x86_64

COPY --from=fpm /bref /opt

COPY runtime/configuration/fpm/bootstrap-fpm /opt/bootstrap
COPY runtime/configuration/fpm/bootstrap-fpm /var/runtime/bootstrap
COPY runtime/configuration/bref.ini /opt/php-ini/bref.ini
COPY runtime/configuration/bref-ext.ini /opt/php-ini/bref-ext.ini
COPY runtime/configuration/bref-ext-opcache.ini /opt/php-ini/bref-ext-opcache.ini

COPY runtime/configuration/fpm/bref-fpm.ini /opt/php-ini/bref-fpm.ini
COPY runtime/configuration/fpm/php-fpm.conf /opt/php-fpm/php-fpm.conf

RUN chmod +x /opt/bootstrap
RUN chmod +x /var/runtime/bootstrap

COPY --from=mbstring /opt/lib/* /opt/lib/
COPY --from=mbstring /opt/php-modules/mbstring.so /opt/php-modules/mbstring.so

COPY --from=bcmath /opt/php-modules/bcmath.so /opt/php-modules/bcmath.so
COPY --from=dom /opt/php-modules/dom.so /opt/php-modules/dom.so
COPY --from=mysqli /opt/php-modules/mysqli.so /opt/php-modules/mysqli.so
COPY --from=mysqli /opt/php-modules/mysqlnd.so /opt/php-modules/mysqlnd.so
COPY --from=opcache /opt/php-modules/opcache.so /opt/php-modules/opcache.so
COPY --from=pdo /opt/php-modules/pdo.so /opt/php-modules/pdo.so
COPY --from=pdo_mysql /opt/php-modules/pdo_mysql.so /opt/php-modules/pdo_mysql.so
COPY --from=simplexml /opt/php-modules/simplexml.so /opt/php-modules/simplexml.so

COPY --from=bref/fpm-source-package /opt/bref-fpm-src /opt/bref-fpm-src

COPY src/Toolbox/Runner.php /opt/bref-src/Toolbox/Runner.php
COPY src/Toolbox/VendorDownloader.php /opt/bref-src/Toolbox/VendorDownloader.php
COPY src/Toolbox/bootstrap.php /opt/bref-src/Toolbox/bootstrap.php

