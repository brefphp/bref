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
COPY bootstraps/cli.bootstrap /tmp/cli.bootstrap
RUN mkdir -p /export; \
 rm -rf /export/*; \
 rm -rf /bootstrap;

ENV PHP_ZIP_NAME='/export/php-7.2'

# Create the PHP CLI layer
RUN zip --quiet --recurse-paths ${PHP_ZIP_NAME}.zip . -x "*php-cgi"; \
 zip -d ${PHP_ZIP_NAME}.zip bref/sbin/php-fpm bin/php-fpm; \
 cp /tmp/cli.bootstrap /bootstrap; \
 chmod 755 /bootstrap; \
 zip -u ${PHP_ZIP_NAME}.zip /bootstrap;

# Create the PHP FPM layer
RUN zip --quiet --recurse-paths ${PHP_ZIP_NAME}-fpm.zip . -x "*php-cgi"
