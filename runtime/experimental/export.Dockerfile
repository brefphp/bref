FROM bref/runtime/php:latest

# Strip all the unneeded symbols from shared libraries to reduce size.
RUN find /opt/bref -type f -name "*.so*" -o -name "*.a"  -exec strip --strip-unneeded {} \;
RUN find /opt/bref -type f -executable -exec sh -c "file -i '{}' | grep -q 'x-executable; charset=binary'" \; -print|xargs strip --strip-all

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

RUN mkdir -p /opt/bin
RUN ln -s /opt/bref/bin/* /opt/bin
RUN ln -s /opt/bref/sbin/* /opt/bin

RUN zip -y -o -9 -r /tmp/php-$(php -r 'echo phpversion();').zip . -x "*php-cgi"; zip -ur /tmp/php-$(php -r 'echo phpversion();').zip /versions.json
RUN cp /tmp/php-$(php -r 'echo phpversion();').zip /tmp/php-cli-$(php -r 'echo phpversion();').zip; zip -d /tmp/php-cli-$(php -r 'echo phpversion();').zip sbin/php-fpm
RUN cp /tmp/php-$(php -r 'echo phpversion();').zip /tmp/php-fpm-$(php -r 'echo phpversion();').zip; zip -d /tmp/php-fpm-$(php -r 'echo phpversion();').zip bin/php

COPY helpers/export.sh /usr/local/bin/export.sh

ENTRYPOINT [ "/usr/local/bin/export.sh" ]