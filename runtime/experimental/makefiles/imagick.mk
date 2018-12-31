SHELL := /bin/bash
.DEFAULT_GOAL := make_imagick

url_imagick = https://github.com/mkoppanen/imagick/archive/${VERSION_IMAGICK}.tar.gz
upper_imagick = $(shell echo imagick| awk '{print toupper($0)}')
build_dir_imagick = ${DEPS}/imagick

fetch_imagick:
	mkdir -p ${build_dir_imagick}
	curl -Ls ${url_imagick} | tar $(shell ${TARGS} ${url_imagick}) ${build_dir_imagick} --strip-components=1

configure_imagick:
	cd ${build_dir_imagick} && \
	/opt/bref/bin/phpize && \
	${build_dir_imagick}/configure --prefix=${TARGET}  --with-php-config=/opt/bref/bin/php-config --with-imagick=${TARGET}

build_imagick:
	cd ${build_dir_imagick} && \
	/usr/bin/make install

version_imagick:
	/usr/local/bin/versions.py add -s extensions -i imagick -v ${VERSION_IMAGICK}

make_imagick: fetch_imagick configure_imagick build_imagick version_imagick
