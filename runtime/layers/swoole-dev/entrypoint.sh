#!/usr/bin/env sh

if [ "xdebug" == $(/opt/bin/php -m | grep xdebug) ]; then
    # If the Xdebug Module was loaded, start laravel serve without Swoole module to support debugging
    PHP_INI_SCAN_DIR="/opt/bref/etc/php/conf.no-swoole.d:/var/task/php/conf.d:/var/task/php/conf.dev.d" && \
    /opt/bin/php /var/task/artisan serve --host 0.0.0.0 --port 8001 &
fi

exec /opt/bin/php /var/task/artisan octane:start --server swoole --port 8000 --host 0.0.0.0
