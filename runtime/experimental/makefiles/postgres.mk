SHELL := /bin/bash
.DEFAULT_GOAL := make_postgres

url_postgres = https://github.com/postgres/postgres/archive/REL$(subst .,_,${VERSION_POSTGRES}).tar.gz
upper_postgres = $(shell echo postgres| awk '{print toupper($0)}')
build_dir_postgres = ${DEPS}/postgres

fetch_postgres:
	mkdir -p ${build_dir_postgres}
	curl -Ls ${url_postgres} | tar $(shell ${TARGS} ${url_postgres}) ${build_dir_postgres} --strip-components=1

configure_postgres:
	cd ${build_dir_postgres} && ${build_dir_postgres}/configure --prefix=${TARGET} --with-openssl --without-readline

build_postgres:
	cd ${build_dir_postgres}/src/interfaces/libpq && /usr/bin/make && /usr/bin/make install && \
	cd ${build_dir_postgres}/src/bin/pg_config && /usr/bin/make && /usr/bin/make install && \
	cd ${build_dir_postgres}/src/backend && /usr/bin/make generated-headers && \
	cd ${build_dir_postgres}/src/include && /usr/bin/make install

version_postgres:
	/usr/local/bin/versions.py add -s libraries -i libpq -v ${VERSION_POSTGRES}

make_postgres: fetch_postgres configure_postgres build_postgres version_postgres
