ARG PHP_VERSION
FROM bref/build-php-$PHP_VERSION as build_extensions

RUN pecl install xdebug
RUN cp $(php -r "echo ini_get('extension_dir');")/xdebug.so /tmp

RUN version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
    && curl -A "Docker" -o /tmp/blackfire.so -L -s "https://packages.blackfire.io/binaries/blackfire-php/1.55.0/blackfire-php-linux_amd64-php-"$version".so" ;

FROM bref/php-${PHP_VERSION}-roadrunner as build_dev

COPY --from=build_extensions /tmp/*.so /tmp/
RUN cp /tmp/*.so $(php -r "echo ini_get('extension_dir');")

FROM bref/php-${PHP_VERSION}-roadrunner

COPY --from=build_dev /opt /opt

EXPOSE 8000

ENV PHP_INI_SCAN_DIR="/opt/bref/etc/php/conf.d:/var/task/php/conf.d:/var/task/php/conf.dev.d"

# Use custom entrypoint
COPY entrypoint.sh /opt/bin/entrypoint.sh
RUN chmod +x /opt/bin/entrypoint.sh

ENTRYPOINT /opt/bin/entrypoint.sh
