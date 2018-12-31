SHELL := /bin/bash
.DEFAULT_GOAL := make_igbinary

url_igbinary = https://github.com/igbinary/igbinary/archive/${VERSION_IGBINARY}.tar.gz
upper_igbinary = $(shell echo igbinary| awk '{print toupper($0)}')
build_dir_igbinary = ${DEPS}/igbinary

fetch_igbinary:
	mkdir -p ${build_dir_igbinary}
	curl -Ls ${url_igbinary} | tar $(shell ${TARGS} ${url_igbinary}) ${build_dir_igbinary} --strip-components=1

configure_igbinary:
	cd ${build_dir_igbinary} && \
	/opt/bref/bin/phpize && \
	${build_dir_igbinary}/configure --prefix=${TARGET} --with-php-config=/opt/bref/bin/php-config --enable-igbinary

build_igbinary:
	cd ${build_dir_igbinary} && \
	/usr/bin/make install

version_igbinary:
	/usr/local/bin/versions.py add -s extensions -i igbinary -v ${VERSION_IGBINARY}

make_igbinary: fetch_igbinary configure_igbinary build_igbinary version_igbinary
