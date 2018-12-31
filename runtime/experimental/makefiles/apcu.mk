.DEFAULT_GOAL := make_apcu

url_apcu = https://github.com/krakjoe/apcu/archive/v${VERSION_APCU}.tar.gz
upper_apcu = $(shell echo apcu| awk '{print toupper($0)}')
build_dir_apcu = ${DEPS}/apcu

fetch_apcu:
	mkdir -p ${build_dir_apcu}
	curl -Ls ${url_apcu} | tar $(shell ${TARGS} ${url_apcu}) ${build_dir_apcu} --strip-components=1

configure_apcu:
	cd ${build_dir_apcu} && \
	/opt/bref/bin/phpize && \
	${build_dir_apcu}/configure --prefix=${TARGET} --with-php-config=/opt/bref/bin/php-config --enable-apcu --enable-static=no

build_apcu:
	cd ${build_dir_apcu} && \
	/usr/bin/make install

version_apcu:
	/usr/local/bin/versions.py add -s extensions -i apcu -v ${VERSION_APCU}

make_apcu: fetch_apcu configure_apcu build_apcu version_apcu
