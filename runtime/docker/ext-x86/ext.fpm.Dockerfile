ARG PHP_VERSION

FROM bref/x86-${PHP_VERSION}-base

ARG PHP_VERSION

RUN yum install -y ${PHP_VERSION}-php-fpm

FROM scratch

ARG PHP_VERSION

COPY --from=0 /opt/remi/${PHP_VERSION}/root/sbin/php-fpm /bref/bin/php-fpm

COPY --from=0 /usr/lib64/libacl.so.1 /bref/lib/libacl.so.1
COPY --from=0 /usr/lib64/libsystemd.so.0 /bref/lib/libsystemd.so.0
COPY --from=0 /usr/lib64/libattr.so.1 /bref/lib/libattr.so.1
COPY --from=0 /usr/lib64/libcap.so.2 /bref/lib/libcap.so.2
COPY --from=0 /usr/lib64/liblz4.so.1 /bref/lib/liblz4.so.1
COPY --from=0 /usr/lib64/libgcrypt.so.11 /bref/lib/libgcrypt.so.11
COPY --from=0 /usr/lib64/libgpg-error.so.0 /bref/lib/libgpg-error.so.0
COPY --from=0 /usr/lib64/libdw.so.1 /bref/lib/libdw.so.1
COPY --from=0 /usr/lib64/libelf.so.1 /bref/lib/libelf.so.1
COPY --from=0 /usr/lib64/libbz2.so.1 /bref/lib/libbz2.so.1
