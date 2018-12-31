SHELL := /bin/bash
.DEFAULT_GOAL := make_sodium

url_sodium = https://github.com/jedisct1/libsodium/archive/${VERSION_SODIUM}.tar.gz
build_dir_sodium = ${DEPS}/sodium

fetch_sodium:
	mkdir -p ${build_dir_sodium}
	curl -Ls ${url_sodium} | tar $(shell ${TARGS} ${url_sodium}) ${build_dir_sodium} --strip-components=1

configure_sodium:
	cd ${build_dir_sodium} && \
	${build_dir_sodium}/autogen.sh && \
	${build_dir_sodium}/configure --prefix=${TARGET}

build_sodium:
	cd ${build_dir_sodium} && \
	/usr/bin/make install

version_sodium:
	/usr/local/bin/versions.py add -s libraries -i libsodium -v ${VERSION_SODIUM}

make_sodium: fetch_sodium configure_sodium build_sodium version_sodium
