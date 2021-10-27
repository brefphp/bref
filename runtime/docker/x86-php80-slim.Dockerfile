FROM public.ecr.aws/lambda/provided:al2-x86_64

COPY --from=bref/x86-php80-base /bref /opt

COPY runtime/configuration/bootstrap /opt/bootstrap
COPY runtime/configuration/bootstrap /var/runtime/bootstrap
COPY runtime/configuration/bref.ini /opt/php-ini/bref.ini

RUN chmod +x /opt/bootstrap
RUN chmod +x /var/runtime/bootstrap

COPY src/Context/Context.php /opt/bref-src/Context/Context.php
COPY src/Context/ContextBuilder.php /opt/bref-src/Context/ContextBuilder.php
COPY src/Toolbox/bootstrap.php /opt/bref-src/Toolbox/bootstrap.php
COPY src/Toolbox/Runner.php /opt/bref-src/Toolbox/Runner.php
COPY src/Toolbox/VendorDownloader.php /opt/bref-src/Toolbox/VendorDownloader.php
COPY src/Runtime/Invoker.php /opt/bref-src/Runtime/Invoker.php
COPY src/Runtime/LambdaRuntime.php /opt/bref-src/Runtime/LambdaRuntime.php
