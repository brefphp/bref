SHELL := /bin/bash
.DEFAULT_GOAL := make_php

url_php = https://github.com/php/php-src/archive/php-${VERSION_PHP}.tar.gz
build_dir_php = ${DEPS}/php

fetch_php:
	mkdir -p ${build_dir_php}
	${CURL} -Ls ${url_php} | tar $(shell ${TARGS} ${url_php}) ${build_dir_php} --strip-components=1

configure_php:
	cd ${build_dir_php} && \
	${build_dir_php}/buildconf --force && \
	CPPFLAGS="-I${TARGET}/include -I/usr/include -I/opt/bref/include/libxml2" ${build_dir_php}/configure \
        --prefix=${TARGET} \
        --exec-prefix=${TARGET} \
        --with-libdir=lib64 \
        --enable-option-checking=fatal \
        --with-config-file-path=${TARGET}/etc/php \
        --with-config-file-scan-dir=${TARGET}/etc/php/config.d:/var/task/php/config.d \
        --enable-fpm \
        --enable-cgi \
        --enable-cli \
        --disable-phpdbg \
        --disable-phpdbg-webhelper \
        --enable-bcmath \
        --enable-calendar \
        --enable-ctype \
        --enable-dom \
        --enable-exif \
        --enable-fileinfo \
        --enable-filter \
        --enable-ftp \
        --enable-gd-jis-conv \
        --enable-hash \
        --enable-intl \
        --enable-json \
        --enable-libxml \
        --enable-mbstring \
        --enable-opcache \
        --enable-opcache-file \
        --enable-pcntl \
        --enable-pdo \
        --enable-phar \
        --enable-session \
        --enable-shared=yes \
        --enable-simplexml \
        --enable-soap \
        --enable-static=no \
        --enable-sysvmsg \
        --enable-sysvsem \
        --enable-sysvshm \
        --enable-tokenizer \
        --enable-xml \
        --enable-xmlwriter \
        --enable-zip \
        --with-curl \
        --with-gd \
        --with-gmp \
        --with-iconv \
        --with-mhash \
        --with-readline \
        --with-mysqli=mysqlnd \
        --with-pdo-mysql=mysqlnd \
        --with-pgsql=${TARGET} \
        --with-pdo-pgsql=${TARGET} \
        --with-openssl=${TARGET} \
        --with-openssl-dir=${TARGET}  \
        --with-libxml-dir=${TARGET}  \
        --with-webp-dir=${TARGET}  \
        --with-png-dir=${TARGET}  \
        --with-jpeg-dir=${TARGET} \
        --with-zlib-dir=${TARGET} \
        --with-sodium=${TARGET} \
        --with-zlib=${TARGET}

build_php:
	cd ${build_dir_php} && \
	$(MAKE) && \
	$(MAKE) install && \
	mkdir -p ${TARGET}/etc/php  && \
    cp php.ini-production ${TARGET}/etc/php/php.ini
	/usr/local/bin/pear.sh


version_php:
	/usr/local/bin/versions.py add -s executables -i php -v ${VERSION_PHP}
	/usr/local/bin/versions.py add -s executables -i php-cgi -v ${VERSION_PHP}
	/usr/local/bin/versions.py add -s executables -i phpize -v ${VERSION_PHP}
	/usr/local/bin/versions.py add -s executables -i php-config -v ${VERSION_PHP}
	/usr/local/bin/versions.py add -s executables -i php-fpm -v ${VERSION_PHP}

make_php: fetch_php configure_php build_php version_php
