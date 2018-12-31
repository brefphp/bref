SHELL := /bin/bash
.DEFAULT_GOAL := make_libssh2

url_libssh2 = https://github.com/libssh2/libssh2/releases/download/libssh2-1.8.0/libssh2-${VERSION_LIBSSH2}.tar.gz
build_dir_libssh2 = ${DEPS}/libssh2

fetch_libssh2:
	mkdir -p ${build_dir_libssh2}
	/usr/bin/curl -Ls ${url_libssh2} | tar $(shell ${TARGS} ${url_libssh2}) ${build_dir_libssh2} --strip-components=1

configure_libssh2:
	mkdir -p ${build_dir_libssh2}/bin
	cd ${build_dir_libssh2}/bin && \
	$(CMAKE) .. \
	-DBUILD_SHARED_LIBS=ON \
	-DCRYPTO_BACKEND=OpenSSL \
	-DENABLE_ZLIB_COMPRESSION=ON \
	-DCMAKE_INSTALL_PREFIX=${TARGET} \
    -DCMAKE_BUILD_TYPE=RELEASE 

build_libssh2:
	cd ${build_dir_libssh2}/bin && \
	$(CMAKE) --build . --target install

version_libssh2:
	/usr/local/bin/versions.py add -s libraries -i libssh2 -v ${VERSION_LIBSSH2}
	
make_libssh2: fetch_libssh2 configure_libssh2 build_libssh2 version_libssh2
