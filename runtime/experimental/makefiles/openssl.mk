SHELL := /bin/bash
.DEFAULT_GOAL := make_openssl
url_openssl := https://github.com/openssl/openssl/archive/OpenSSL_$(subst .,_,${VERSION_OPENSSL}).tar.gz
build_dir_openssl = ${DEPS}/openssl

fetch_openssl:
	
	mkdir -p ${build_dir_openssl}
	/usr/bin/curl -Ls ${url_openssl} | tar $(shell ${TARGS} ${url_openssl}) ${build_dir_openssl} --strip-components=1
	
configure_openssl:
	mkdir -p ${TARGET}/etc/ssl
	cd ${build_dir_openssl} &&		\
	${build_dir_openssl}/config 	\
		--prefix=${TARGET}			\
		--openssldir=${TARGET}/ssl	\
		--release					\
		no-tests					\
		shared						\
		zlib

build_openssl:
	cd ${build_dir_openssl} && \
	$(MAKE) install
	/usr/bin/curl -k -o ${CA_BUNDLE} ${CA_BUNDLE_SOURCE}

version_openssl:
	/usr/local/bin/versions.py add -s libraries -i openssl -v ${VERSION_OPENSSL}
	/usr/local/bin/versions.py add -s executables -i c_rehash -v ${VERSION_OPENSSL}
	/usr/local/bin/versions.py add -s executables -i openssl -v ${VERSION_OPENSSL}

make_openssl: fetch_openssl configure_openssl build_openssl version_openssl
