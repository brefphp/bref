FROM public.ecr.aws/lambda/provided:al2-x86_64

COPY --from=bref/x86-php80-base /bref /opt

COPY runtime/configuration/bootstrap /opt/bootstrap
COPY runtime/configuration/bootstrap /var/runtime/bootstrap
COPY runtime/configuration/bref-function.ini /opt/php-ini/bref.ini

COPY --from=bref/x86-php80-ext-mbstring /opt/lib/* /opt/lib/
COPY --from=bref/x86-php80-ext-mbstring /opt/php-modules/mbstring.so /opt/php-modules/mbstring.so

COPY --from=bref/x86-php80-ext-bcmath /opt/php-modules/bcmath.so /opt/php-modules/bcmath.so
COPY --from=bref/x86-php80-ext-ctype /opt/php-modules/ctype.so /opt/php-modules/ctype.so
COPY --from=bref/x86-php80-ext-dom /opt/php-modules/dom.so /opt/php-modules/dom.so
COPY --from=bref/x86-php80-ext-exif /opt/php-modules/exif.so /opt/php-modules/exif.so
COPY --from=bref/x86-php80-ext-fileinfo /opt/php-modules/fileinfo.so /opt/php-modules/fileinfo.so
COPY --from=bref/x86-php80-ext-ftp /opt/php-modules/ftp.so /opt/php-modules/ftp.so
COPY --from=bref/x86-php80-ext-gettext /opt/php-modules/gettext.so /opt/php-modules/gettext.so
COPY --from=bref/x86-php80-ext-iconv /opt/php-modules/iconv.so /opt/php-modules/iconv.so

#TODO: figure out why it doesn't work
#COPY --from=bref/x86-php80-ext-mysqli /opt/php-modules/mysqli.so /opt/php-modules/mysqli.so

COPY package/src/Bref.php /opt/bref-src/Bref.php
COPY package/src/Context/Context.php /opt/bref-src/Context/Context.php
COPY package/src/Context/ContextBuilder.php /opt/bref-src/Context/ContextBuilder.php
COPY package/src/Toolbox/bootstrap.php /opt/bref-src/Toolbox/bootstrap.php
COPY package/src/Toolbox/Runner.php /opt/bref-src/Toolbox/Runner.php
COPY package/src/Toolbox/VendorDownloader.php /opt/bref-src/Toolbox/VendorDownloader.php
COPY package/src/Runtime/Invoker.php /opt/bref-src/Runtime/Invoker.php
COPY package/src/Runtime/LambdaRuntime.php /opt/bref-src/Runtime/LambdaRuntime.php