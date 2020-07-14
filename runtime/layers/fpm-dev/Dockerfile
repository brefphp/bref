ARG PHP_VERSION
FROM bref/build-php-$PHP_VERSION as build_extensions
# We need that variable so we re-import it again (args are cleared after each FROM)
ARG PHP_VERSION

RUN if [ "$PHP_VERSION" = "80" ] ; \
    then \
        echo "Installing xdebug from master to support PHP 8.0" \
        && git clone git://github.com/xdebug/xdebug.git \
        && cd xdebug \
        && phpize \
        && ./configure --enable-xdebug \
        && make \
        && make install \
        && cd .. \
        && rm -rf xdebug ; \
    else \
        pecl install xdebug ; \
    fi
RUN cp $(php -r "echo ini_get('extension_dir');")/xdebug.so /tmp

RUN if [ "$PHP_VERSION" != "80" ] ; \
    then \
        version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;")-zts \
        && curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/$version \
        && mkdir -p /tmp/blackfire \
        && tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire \
        && cp /tmp/blackfire/blackfire-*.so /tmp/blackfire.so \
        && rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz ; \
    fi

FROM bref/php-${PHP_VERSION}-fpm as build_dev

USER root
COPY --from=build_extensions /tmp/*.so /tmp/
RUN cp /tmp/*.so $(php -r "echo ini_get('extension_dir');")

FROM bref/php-${PHP_VERSION}-fpm

COPY --from=build_dev  /opt /opt
# Override the config so that PHP-FPM listens on port 9000
COPY php-fpm.conf /opt/bref/etc/php-fpm.conf

EXPOSE 9001

# Clear the parent entrypoint
ENTRYPOINT []

ENV PHP_INI_SCAN_DIR="/opt/bref/etc/php/conf.d:/var/task/php/conf.d:/var/task/php/conf.dev.d"

# Run PHP-FPM
# opcache.validate_timestamps=1 : cancels the flag in the base configuration so that files are reloaded
CMD /opt/bin/php-fpm --nodaemonize --fpm-config /opt/bref/etc/php-fpm.conf -d opcache.validate_timestamps=1 --force-stderr
