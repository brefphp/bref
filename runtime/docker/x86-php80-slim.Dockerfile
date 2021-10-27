FROM public.ecr.aws/lambda/provided:al2-x86_64

COPY --from=bref/x86-php80-base /bref /opt

COPY runtime/configuration/bootstrap /opt/bootstrap
COPY runtime/configuration/bootstrap /var/runtime/bootstrap
COPY runtime/configuration/bref.ini /opt/php-ini/bref.ini

COPY package/src/Bref.php /opt/bref-src/Bref.php
COPY package/src/Context/Context.php /opt/bref-src/Context/Context.php
COPY package/src/Context/ContextBuilder.php /opt/bref-src/Context/ContextBuilder.php
COPY package/src/Toolbox/bootstrap.php /opt/bref-src/Toolbox/bootstrap.php
COPY package/src/Toolbox/Runner.php /opt/bref-src/Toolbox/Runner.php
COPY package/src/Toolbox/VendorDownloader.php /opt/bref-src/Toolbox/VendorDownloader.php
COPY package/src/Runtime/Invoker.php /opt/bref-src/Runtime/Invoker.php
COPY package/src/Runtime/LambdaRuntime.php /opt/bref-src/Runtime/LambdaRuntime.php
