FROM bref/x86-php80-base

RUN yum install -y php80-php-fpm php80-php-posix

RUN /bin/cat /opt/remi/php80/root/sbin/php-fpm > /bref/bin/php-fpm

RUN chmod +x /bref/bin/php-fpm

RUN /bin/cat /opt/remi/php80/root/lib64/php/modules/posix.so > /bref/php-modules/posix.so

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

COPY --from=bref/x86-php80-base /bref /opt

COPY --from=0 /bref/bin/php-fpm /opt/bin/php-fpm
COPY --from=0 /bref/lib/* /opt/lib/
COPY --from=0 /bref/php-modules/* /opt/php-modules/

COPY --from=bref/x86-php80-ext-mbstring /opt/lib/* /opt/lib/
COPY --from=bref/x86-php80-ext-mbstring /opt/php-modules/* /opt/php-modules/

COPY --from=bref/x86-php80-ext-bcmath /opt/php-modules/* /opt/php-modules/

COPY runtime/configuration/fpm/bref-fpm.ini /opt/php-ini/bref.ini
COPY runtime/configuration/fpm/php-fpm.conf /opt/php-fpm/php-fpm.conf

COPY runtime/configuration/fpm/bootstrap-fpm /opt/bootstrap
COPY runtime/configuration/fpm/bootstrap-fpm /var/runtime/bootstrap

RUN chmod +x /opt/bootstrap
RUN chmod +x /var/runtime/bootstrap

COPY src/Toolbox/Runner.php /opt/bref-src/Toolbox/Runner.php
COPY src/Toolbox/VendorDownloader.php /opt/bref-src/Toolbox/VendorDownloader.php
COPY src/Toolbox/bootstrap.php /opt/bref-src/Toolbox/bootstrap.php

