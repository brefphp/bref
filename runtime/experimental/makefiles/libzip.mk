SHELL := /bin/bash
.DEFAULT_GOAL := make_libzip

url_libzip = https://github.com/nih-at/libzip/archive/rel-$(subst .,-,${VERSION_LIBZIP}).tar.gz
build_dir_libzip = ${DEPS}/libzip

fetch_libzip:
	mkdir -p ${build_dir_libzip}/build
	${CURL} -Ls ${url_libzip} | tar $(shell ${TARGS} ${url_libzip}) ${build_dir_libzip} --strip-components=1

configure_libzip:
	cd ${build_dir_libzip}/build && \
	$(CMAKE) .. \
	-DCMAKE_INSTALL_PREFIX=${TARGET} \
	-DCMAKE_BUILD_TYPE=RELEASE 

build_libzip:
	cd ${build_dir_libzip}/build && \
	$(MAKE) install

version_libzip:
	/usr/local/bin/versions.py add -s libraries -i version_libzip -v ${VERSION_LIBZIP}

make_libzip: fetch_libzip configure_libzip build_libzip version_libzip
