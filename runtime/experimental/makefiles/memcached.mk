SHELL := /bin/bash
.DEFAULT_GOAL := make_memcached

url_memcached = https://launchpad.net/libmemcached/$(shell echo ${VERSION_MEMCACHED}|cut -d. -f1,2)/${VERSION_MEMCACHED}/+download/libmemcached-${VERSION_MEMCACHED}.tar.gz
upper_memcached = $(shell echo memcached| awk '{print toupper($0)}')
build_dir_memcached = ${DEPS}/memcached

fetch_memcached:
	mkdir -p ${build_dir_memcached}
	curl -Ls ${url_memcached} | tar $(shell ${TARGS} ${url_memcached}) ${build_dir_memcached} --strip-components=1

configure_memcached:
	cd ${build_dir_memcached} && \
	${build_dir_memcached}/configure --prefix=${TARGET}

build_memcached:
	cd ${build_dir_memcached} && \
	/usr/bin/make install

version_memcached:
	/usr/local/bin/versions.py add -s libraries -i memcached -v ${VERSION_MEMCACHED}

make_memcached: fetch_memcached configure_memcached build_memcached version_memcached
