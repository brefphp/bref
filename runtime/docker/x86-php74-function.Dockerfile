FROM public.ecr.aws/lambda/provided:al2-x86_64

COPY --from=bref/x86-php74-base /bref /opt

COPY runtime/configuration/bootstrap /opt/bootstrap
COPY runtime/configuration/bootstrap /var/runtime/bootstrap
COPY runtime/configuration/bref-function.ini /opt/php-ini/bref.ini
COPY runtime/configuration/php74.ini /opt/php-ini/php74.ini

RUN chmod +x /opt/bootstrap
RUN chmod +x /var/runtime/bootstrap

COPY --from=bref/x86-php74-ext-mbstring /opt/lib/* /opt/lib/
COPY --from=bref/x86-php74-ext-mbstring /opt/php-modules/* /opt/php-modules/

COPY --from=bref/x86-php74-ext-bcmath /opt/php-modules/bcmath.so /opt/php-modules/bcmath.so
COPY --from=bref/x86-php74-ext-ctype /opt/php-modules/ctype.so /opt/php-modules/ctype.so
COPY --from=bref/x86-php74-ext-dom /opt/php-modules/dom.so /opt/php-modules/dom.so
COPY --from=bref/x86-php74-ext-exif /opt/php-modules/exif.so /opt/php-modules/exif.so
COPY --from=bref/x86-php74-ext-fileinfo /opt/php-modules/fileinfo.so /opt/php-modules/fileinfo.so
COPY --from=bref/x86-php74-ext-ftp /opt/php-modules/ftp.so /opt/php-modules/ftp.so
COPY --from=bref/x86-php74-ext-gettext /opt/php-modules/gettext.so /opt/php-modules/gettext.so
COPY --from=bref/x86-php74-ext-iconv /opt/php-modules/iconv.so /opt/php-modules/iconv.so

COPY src/Context/Context.php /opt/bref-src/Context/Context.php
COPY src/Context/ContextBuilder.php /opt/bref-src/Context/ContextBuilder.php
COPY src/Toolbox/bootstrap.php /opt/bref-src/Toolbox/bootstrap.php
COPY src/Toolbox/Runner.php /opt/bref-src/Toolbox/Runner.php
COPY src/Toolbox/VendorDownloader.php /opt/bref-src/Toolbox/VendorDownloader.php
COPY src/Runtime/Invoker.php /opt/bref-src/Runtime/Invoker.php
COPY src/Runtime/LambdaRuntime.php /opt/bref-src/Runtime/LambdaRuntime.php
