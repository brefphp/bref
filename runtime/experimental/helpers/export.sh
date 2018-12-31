#!/bin/bash
cp /tmp/php-$(php -r 'echo phpversion();').zip /export/php-fpm-$(php -r 'echo phpversion();').zip
cp /tmp/php-cli-$(php -r 'echo phpversion();').zip /export/php-default-$(php -r 'echo phpversion();').zip