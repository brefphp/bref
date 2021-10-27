# This file is only useful when the extension is simple/self-contained or if all of it's
# library dependencies are already included in Amazon Linux 2 or Bref's base image.
# That means we only have to extract the `extension.so` file.

ARG PHP_VERSION

FROM bref/x86-${PHP_VERSION}-base as standard

ARG PHP_VERSION

ARG EXTENSION

RUN yum install -y ${PHP_VERSION}-php-${EXTENSION}

FROM scratch

ARG PHP_VERSION

ARG EXTENSION

COPY --from=0 /opt/remi/${PHP_VERSION}/root/lib64/php/modules/${EXTENSION}.so /opt/php-modules/${EXTENSION}.so
