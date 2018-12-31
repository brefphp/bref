SHELL := /bin/bash
.DEFAULT_GOAL := make_php_memcached

url_php_memcached = https://github.com/php-memcached-dev/php-memcached/archive/v${VERSION_PHP_MEMCACED}.tar.gz
upper_php_memcached = $(shell echo php_memcached| awk '{print toupper($0)}')
build_dir_php_memcached = ${DEPS}/php_memcached

fetch_php_memcached:
	mkdir -p ${build_dir_php_memcached}
	curl -Ls ${url_php_memcached} | tar $(shell ${TARGS} ${url_php_memcached}) ${build_dir_php_memcached} --strip-components=1

configure_php_memcached:
	cd ${build_dir_php_memcached} && \
	/opt/bref/bin/phpize && \
	${build_dir_php_memcached}/configure --prefix=${TARGET}  \
	--with-php-config=/opt/bref/bin/php-config --enable-memcached \
	--with-libmemcached-dir=${TARGET} --with-zlib-dir=${TARGET} --disable-memcached-sasl

build_php_memcached:
	cd ${build_dir_php_memcached} && \
	/usr/bin/make install

version_php_memcached:
	/usr/local/bin/versions.py add -s extensions -i php_memcached -v ${VERSION_PHP_MEMCACED}

make_php_memcached: fetch_php_memcached configure_php_memcached build_php_memcached version_php_memcached
