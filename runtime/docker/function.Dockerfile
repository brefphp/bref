ARG AWS_TAG

ARG ARCHITECTURE

ARG PHP_VERSION

FROM bref/${ARCHITECTURE}-${PHP_VERSION}-base as base
FROM bref/${ARCHITECTURE}-${PHP_VERSION}-ext-default as ext-default


FROM public.ecr.aws/lambda/provided:${AWS_TAG}

COPY --from=base /bref /opt
COPY --from=ext-default /opt/lib/* /opt/lib/
COPY --from=ext-default /opt/php-modules/*.so /opt/php-modules/

COPY runtime/configuration/bootstrap /opt/bootstrap
COPY runtime/configuration/bootstrap /var/runtime/bootstrap
COPY runtime/configuration/bref.ini /opt/php-ini/bref.ini
COPY runtime/configuration/bref-ext.ini /opt/php-ini/bref-ext.ini
COPY runtime/configuration/bref-ext-opcache.ini /opt/php-ini/bref-ext-opcache.ini



RUN chmod +x /opt/bootstrap
RUN chmod +x /var/runtime/bootstrap

COPY src/Context/Context.php /opt/bref-src/Context/Context.php
COPY src/Context/ContextBuilder.php /opt/bref-src/Context/ContextBuilder.php
COPY src/Toolbox/bootstrap.php /opt/bref-src/Toolbox/bootstrap.php
COPY src/Toolbox/Runner.php /opt/bref-src/Toolbox/Runner.php
COPY src/Toolbox/VendorDownloader.php /opt/bref-src/Toolbox/VendorDownloader.php
COPY src/Runtime/Invoker.php /opt/bref-src/Runtime/Invoker.php
COPY src/Runtime/LambdaRuntime.php /opt/bref-src/Runtime/LambdaRuntime.php