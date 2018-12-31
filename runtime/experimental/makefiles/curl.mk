SHELL := /bin/bash
.DEFAULT_GOAL := make_curl
build_dir_curl = ${DEPS}/curl

fetch_curl:
	mkdir -p ${build_dir_curl}
	/usr/bin/git clone https://github.com/curl/curl.git ${build_dir_curl}
	cd ${build_dir_curl} && \
	/usr/bin/git checkout curl-$(subst .,_,${VERSION_CURL})

configure_curl:
	cd ${build_dir_curl} && \
	${build_dir_curl}/buildconf && \
	${build_dir_curl}/configure \
        --prefix=${TARGET} \
		--with-ca-bundle=${CA_BUNDLE} \
        --enable-shared \
        --disable-static \
		--enable-optimize \
		--disable-warnings \
        --disable-dependency-tracking \
		--with-zlib \
		--enable-http \
		--enable-ftp  \
		--enable-file \
		--enable-ldap \
		--enable-ldaps  \
		--enable-proxy  \
		--enable-tftp \
		--enable-ipv6 \
		--enable-openssl-auto-load-config \
		--enable-cookies \
		--with-gnu-ld \
		--with-ssl \
		--with-libssh2

build_curl:
	cd ${build_dir_curl} && \
	$(MAKE) install

version_curl:
	/usr/local/bin/versions.py add -s libraries -i curl -v ${VERSION_CURL}
	/usr/local/bin/versions.py add -s executables -i curl -v ${VERSION_CURL}
	/usr/local/bin/versions.py add -s executables -i curl-config -v ${VERSION_CURL}
make_curl: fetch_curl configure_curl build_curl version_curl
