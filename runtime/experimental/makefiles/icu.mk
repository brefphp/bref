SHELL := /bin/bash
.DEFAULT_GOAL := make_icu
url_icu = https://github.com/unicode-org/icu/releases/download/release-$(subst .,-,${VERSION_ICU})/icu4c-$(subst .,_,${VERSION_ICU})-src.tgz
build_dir_icu = ${DEPS}/icu

fetch_icu:
	mkdir -p ${build_dir_icu}
	${CURL} -Ls ${url_icu} | tar $(shell ${TARGS} ${url_icu}) ${build_dir_icu} --strip-components=1

configure_icu:
	cd ${build_dir_icu}/source && \
	chmod +x runConfigureICU configure install-sh && \
	./runConfigureICU Linux && \
	${build_dir_icu}/source/./configure \
        --prefix=${TARGET} \
        --enable-shared \
        --with-library-bits=64 \
        --with-data-packaging=library \
        --enable-tests=no \
        --enable-samples=no \
        --disable-static

build_icu:
	cd ${build_dir_icu}/source && \
	CXXFLAGS="-std=c++11 ${FLAGS}" $(MAKE) install

version_icu:
	/usr/local/bin/versions.py add -s libraries -i icu -v ${VERSION_ICU}

make_icu: fetch_icu configure_icu build_icu version_icu
