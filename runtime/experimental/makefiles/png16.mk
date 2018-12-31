SHELL := /bin/bash
.DEFAULT_GOAL := make_png16
url_png16 = http://${SOURCEFORGE_MIRROR}.dl.sourceforge.net/project/libpng/libpng16/${VERSION_PNG16}/libpng-${VERSION_PNG16}.tar.xz
build_dir_png16 = ${DEPS}/png16

fetch_png16:
	mkdir -p ${build_dir_png16}
	${CURL} -Ls ${url_png16} | tar $(shell ${TARGS} ${url_png16}) ${build_dir_png16} --strip-components=1

configure_png16:
	cd ${build_dir_png16} && \
	${build_dir_png16}/configure \
        --prefix=${TARGET} \
        --enable-shared \
        --disable-static \
        --disable-dependency-tracking

build_png16:
	cd ${build_dir_png16} && \
	$(MAKE) install-strip

version_png16:
	/usr/local/bin/versions.py add -s libraries -i libpng16 -v ${VERSION_PNG16}
	/usr/local/bin/versions.py add -s executables -i libpng16-config -v ${VERSION_PNG16}
	/usr/local/bin/versions.py add -s executables -i png-fix-itxt -v ${VERSION_PNG16}
	/usr/local/bin/versions.py add -s executables -i pngfix -v ${VERSION_PNG16}

make_png16: fetch_png16 configure_png16 build_png16 version_png16
