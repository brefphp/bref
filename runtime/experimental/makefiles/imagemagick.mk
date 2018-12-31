SHELL := /bin/bash
.DEFAULT_GOAL := make_imagemagick

url_imagemagick = https://github.com/ImageMagick/ImageMagick/archive/${VERSION_IMAGEMAGICK}.tar.gz
build_dir_imagemagick = ${DEPS}/imagemagick

fetch_imagemagick:
	mkdir -p ${build_dir_imagemagick}
	${CURL} -Ls ${url_imagemagick} | tar $(shell ${TARGS} ${url_imagemagick}) ${build_dir_imagemagick} --strip-components=1

configure_imagemagick:
	cd ${build_dir_imagemagick} && \
	${build_dir_imagemagick}/configure \
        --prefix=${TARGET} \
        --sysconfdir=${TARGET}/etc \
        --enable-hdri     \
        --with-gslib    \
        --with-rsvg     \
        --disable-static

build_imagemagick:
	cd ${build_dir_imagemagick} && \
	$(MAKE) install-strip

version_imagemagick:
	/usr/local/bin/versions.py add -s libraries -i imagemagick -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/Magick++-config -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/MagickCore-config -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/MagickWand-config -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/animate -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/compare -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/composite -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/conjur -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/convert -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/display -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/identify -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/import -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/magick -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/magick-script -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/mogrify -v ${VERSION_IMAGEMAGICK}
	/usr/local/bin/versions.py add -s executables -i /opt/bref/bin/montage -v ${VERSION_IMAGEMAGICK}

make_imagemagick: fetch_imagemagick configure_imagemagick build_imagemagick version_imagemagick
