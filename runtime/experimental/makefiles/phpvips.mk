SHELL := /bin/bash
.DEFAULT_GOAL := make_phpvips

url_phpvips = https://github.com/libvips/php-vips-ext/archive/v${VERSION_PHPVIPS}.tar.gz
build_dir_phpvips = ${DEPS}/phpvips
ifeq ($(suffix $(url_phpvips)), .gz)
	phpvips_args=xzC
else
	ifeq ($(suffix $(url_phpvips)), tgz)
		phpvips_args=xzC
	else
		ifeq ($(suffix $(url_phpvips)), bz2)
			phpvips_args=xjC
		else
			phpvips_args=xJC
		endif
	endif
endif

fetch_phpvips:
	mkdir -p ${build_dir_phpvips}
	curl -Ls ${url_phpvips} | tar $(phpvips_args) ${build_dir_phpvips} --strip-components=1

configure_phpvips:
	cd ${build_dir_phpvips} && \
	${TARGET}/bin/phpize  && \
    ${build_dir_phpvips}/configure \
        --with-php-config=${TARGET}/bin/php-config \
        --with-libdir=${TARGET}/lib \
        --with-vips

build_phpvips:
	cd ${build_dir_phpvips} && \
	/usr/bin/make install && \
	mkdir -p ${TARGET}/modules && \
	echo "extension=vips.so" > ${TARGET}/modules/ext-phpvips.ini

version_phpvips:
	/usr/local/bin/versions.py add -s libraries -i phpvips -v ${VERSION_EXT_VIPS_PHP}

make_phpvips: fetch_phpvips configure_phpvips build_phpvips version_phpvips
