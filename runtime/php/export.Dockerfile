FROM bref/runtime/php:latest
# Use bash instead of sh because we use bash-specific features below
SHELL ["/bin/bash", "-c"]

# We do not support running pear functions in Lambda
RUN rm -rf /opt/bref/lib/php/PEAR
RUN rm -rf /opt/bref/share/doc
RUN rm -rf /opt/bref/share/man
RUN rm -rf /opt/bref/share/gtk-doc
RUN rm -rf /opt/bref/include
RUN rm -rf /opt/bref/tests
RUN rm -rf /opt/bref/doc
RUN rm -rf /opt/bref/docs
RUN rm -rf /opt/bref/man
RUN rm -rf /opt/bref/www
RUN rm -rf /opt/bref/cfg
RUN rm -rf /opt/bref/libexec
RUN rm -rf /opt/bref/var
RUN rm -rf /opt/bref/data

WORKDIR /opt
RUN mkdir -p /export; \
 rm -rf /export/*; \
 rm -rf /bootstrap;

ENV PHP_ZIP_NAME='/export/php-72'

# Create the PHP CLI layer
COPY layers/function/bootstrap /tmp/function.bootstrap
COPY layers/function/php.ini /tmp/function.php.ini
RUN zip --quiet --recurse-paths ${PHP_ZIP_NAME}.zip . --exclude "*php-cgi"; \
 zip --delete ${PHP_ZIP_NAME}.zip bref/sbin/php-fpm bin/php-fpm; \
 cp /tmp/function.bootstrap /bootstrap; \
 chmod 755 /bootstrap; \
 zip --update ${PHP_ZIP_NAME}.zip /bootstrap; \
 cp /tmp/function.php.ini /php.ini; \
 zip --update ${PHP_ZIP_NAME}.zip /php.ini;

# Create the PHP FPM layer
COPY layers/fpm/bootstrap /tmp/fpm.bootstrap
COPY layers/fpm/php.ini /tmp/fpm.php.ini
COPY layers/fpm/php-fpm.conf /tmp/fpm.php-fpm.conf
RUN zip --quiet --recurse-paths ${PHP_ZIP_NAME}-fpm.zip . --exclude "*php-cgi"; \
 zip --delete ${PHP_ZIP_NAME}-fpm.zip bref/bin/php /bin/php \
 cp /tmp/fpm.bootstrap /bootstrap; \
 chmod 755 /bootstrap; \
 zip --update ${PHP_ZIP_NAME}-fpm.zip /bootstrap; \
 cp /tmp/fpm.php.ini /php.ini; \
 zip --update ${PHP_ZIP_NAME}.zip /php.ini; \
 cp /tmp/fpm.php-fpm.conf /php-fpm.conf; \
 zip --update ${PHP_ZIP_NAME}-fpm.zip /php-fpm.conf;
