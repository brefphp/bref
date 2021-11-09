FROM public.ecr.aws/lambda/provided:al2-x86_64 as base

RUN yum install -y \
        https://dl.fedoraproject.org/pub/epel/epel-release-latest-7.noarch.rpm \
        https://rpms.remirepo.net/enterprise/remi-release-7.rpm \
        yum-utils \
        epel-release \
        curl

RUN yum-config-manager --enable remi-php74

RUN yum update -y && yum upgrade -y

RUN yum install -y php74-php unzip

# This will force yum to download remi metadata so that extension installation will be faster
RUN yum update --assumeno

RUN mkdir /bref \
&&  mkdir -p /bref/bin \
&&  mkdir -p /bref/lib \
&&  mkdir -p /bref/php-modules

# PHP Binary
RUN /bin/cat /opt/remi/php74/root/usr/bin/php > /bref/bin/php

RUN chmod +x /bref/bin/php

# Bref itself doesn't work without curl and json extensions
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/curl.so > /bref/php-modules/curl.so

# PHP 7.4 doesn't have json enabled by default.
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/json.so > /bref/php-modules/json.so
COPY runtime/configuration/base/php74.ini /bref/php-ini/php74.ini

# Shared Libraries

# These files are included on Amazon Linux 2

# RUN /bin/cat /lib64/librt.so.1 > /bref/lib/librt.so.1
# RUN /bin/cat /lib64/libstdc++.so.6 > /bref/lib/libstdc++.so.6
# RUN /bin/cat /lib64/libutil.so.1 > /bref/lib/libutil.so.1
# RUN /bin/cat /lib64/libxml2.so.2 > /bref/lib/libxml2.so.2
# RUN /bin/cat /lib64/libssl.so.10 > /bref/lib/libssl.so.10
# RUN /bin/cat /lib64/libz.so.1 > /bref/lib/libz.so.1
# RUN /bin/cat /lib64/libselinux.so.1 > /bref/lib/libselinux.so.1

RUN /bin/cat /lib64/libtinfo.so.5 > /bref/lib/libtinfo.so.5
RUN /bin/cat /lib64/libcrypt.so.1 > /bref/lib/libcrypt.so.1
RUN /bin/cat /lib64/libresolv.so.2 > /bref/lib/libresolv.so.2
RUN /bin/cat /lib64/libncurses.so.5 > /bref/lib/libncurses.so.5
RUN /bin/cat /lib64/libm.so.6 > /bref/lib/libm.so.6
RUN /bin/cat /lib64/libdl.so.2 > /bref/lib/libdl.so.2
RUN /bin/cat /lib64/libgssapi_krb5.so.2 > /bref/lib/libgssapi_krb5.so.2
RUN /bin/cat /lib64/libkrb5.so.3 > /bref/lib/libkrb5.so.3
RUN /bin/cat /lib64/libk5crypto.so.3 > /bref/lib/libk5crypto.so.3
RUN /bin/cat /lib64/libcom_err.so.2 > /bref/lib/libcom_err.so.2
RUN /bin/cat /lib64/libcrypto.so.10 > /bref/lib/libcrypto.so.10
RUN /bin/cat /lib64/libedit.so.0 > /bref/lib/libedit.so.0
RUN /bin/cat /lib64/libc.so.6 > /bref/lib/libc.so.6
RUN /bin/cat /lib64/libpthread.so.0 > /bref/lib/libpthread.so.0
RUN /bin/cat /lib64/ld-linux-x86-64.so.2 > /bref/lib/ld-linux-x86-64.so.2
RUN /bin/cat /lib64/libgcc_s.so.1 > /bref/lib/libgcc_s.so.1
RUN /bin/cat /lib64/liblzma.so.5 > /bref/lib/liblzma.so.5
RUN /bin/cat /lib64/libkrb5support.so.0 > /bref/lib/libkrb5support.so.0
RUN /bin/cat /lib64/libkeyutils.so.1 > /bref/lib/libkeyutils.so.1
RUN /bin/cat /lib64/libtinfo.so.6 > /bref/lib/libtinfo.so.6
RUN /bin/cat /lib64/libpcre.so.1 > /bref/lib/libpcre.so.1

# cURL

# These files are included on Amazon Linux 2
# RUN /bin/cat /lib64/libssh2.so.1 > /bref/lib/libssh2.so.1
# RUN /bin/cat /lib64/libunistring.so.0 > /bref/lib/libunistring.so.0
# RUN /bin/cat /lib64/libsasl2.so.3 > /bref/lib/libsasl2.so.3
# RUN /bin/cat /lib64/libssl3.so > /bref/lib/libssl3.so
# RUN /bin/cat /lib64/libsmime3.so > /bref/lib/libsmime3.so

RUN /bin/cat /lib64/libcurl.so.4 > /bref/lib/libcurl.so.4
RUN /bin/cat /lib64/libnghttp2.so.14 > /bref/lib/libnghttp2.so.14
RUN /bin/cat /lib64/libidn2.so.0 > /bref/lib/libidn2.so.0
RUN /bin/cat /lib64/libldap-2.4.so.2 > /bref/lib/libldap-2.4.so.2
RUN /bin/cat /lib64/liblber-2.4.so.2 > /bref/lib/liblber-2.4.so.2
RUN /bin/cat /lib64/libnss3.so > /bref/lib/libnss3.so
RUN /bin/cat /lib64/libnssutil3.so > /bref/lib/libnssutil3.so
RUN /bin/cat /lib64/libplds4.so > /bref/lib/libplds4.so
RUN /bin/cat /lib64/libplc4.so > /bref/lib/libplc4.so
RUN /bin/cat /lib64/libnspr4.so > /bref/lib/libnspr4.so

# Default PHP Extensions already installed

RUN /bin/cat /usr/lib64/libsodium.so.23 > /bref/lib/libsodium.so.23
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/sodium.so > /bref/php-modules/sodium.so

RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/ctype.so > /bref/php-modules/ctype.so
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/exif.so > /bref/php-modules/exif.so
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/fileinfo.so > /bref/php-modules/fileinfo.so
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/ftp.so > /bref/php-modules/ftp.so
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/gettext.so > /bref/php-modules/gettext.so
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/iconv.so > /bref/php-modules/iconv.so
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/sockets.so > /bref/php-modules/sockets.so
RUN /bin/cat /opt/remi/php74/root/lib64/php/modules/tokenizer.so > /bref/php-modules/tokenizer.so
