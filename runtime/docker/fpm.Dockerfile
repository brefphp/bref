ARG AWS_TAG

ARG ARCHITECTURE

ARG PHP_VERSION

FROM bref/${ARCHITECTURE}-${PHP_VERSION}-base as base
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-default as ext-default
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-fpm as fpm

FROM public.ecr.aws/lambda/provided:${AWS_TAG}

COPY --from=base /bref /opt
COPY --from=fpm /bref /opt
COPY --from=ext-default /opt/lib/* /opt/lib/
COPY --from=ext-default /opt/php-modules/*.so /opt/php-modules/
COPY --from=bref/fpm-source-package /opt/bref-fpm-src /opt/bref-fpm-src

COPY runtime/configuration/fpm/bootstrap-fpm /opt/bootstrap
COPY runtime/configuration/fpm/bootstrap-fpm /var/runtime/bootstrap
COPY runtime/configuration/bref.ini /opt/php-ini/bref.ini
COPY runtime/configuration/bref-ext.ini /opt/php-ini/bref-ext.ini
COPY runtime/configuration/bref-ext-opcache.ini /opt/php-ini/bref-ext-opcache.ini

COPY runtime/configuration/fpm/php-fpm.conf /opt/php-fpm/php-fpm.conf

RUN chmod +x /opt/bootstrap
RUN chmod +x /var/runtime/bootstrap

COPY src/Toolbox/Runner.php /opt/bref-src/Toolbox/Runner.php
COPY src/Toolbox/VendorDownloader.php /opt/bref-src/Toolbox/VendorDownloader.php
COPY src/Toolbox/bootstrap.php /opt/bref-src/Toolbox/bootstrap.php

