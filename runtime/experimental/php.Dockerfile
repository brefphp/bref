FROM bref/runtime/libs:latest

# https://github.com/php/php-src/releases
# http://www.linuxfromscratch.org/blfs/view/svn/general/php.html
# Needs:
#   - libxml2
#   - libxslt
#   - pcre
#   - freetype
#   - libjpeg-turbo
#   - libpng
#   - libtiff
#   - curl
#   - openssl
# Needed by:
#   - phpvips
#build php
ARG php
ENV VERSION_PHP=${php}
COPY makefiles/php.mk ${DEPS}/php.mk
COPY helpers/pear.sh /usr/local/bin/pear.sh
RUN /usr/bin/make -f ${DEPS}/php.mk fetch_php
RUN /usr/bin/make -f ${DEPS}/php.mk configure_php
RUN /usr/bin/make -f ${DEPS}/php.mk

RUN mkdir -p /opt/bref/etc/php/config.d

RUN /opt/bref/bin/pecl install igbinary
RUN echo "extension=igbinary.so" | tee -a /opt/bref/etc/php/config.d/igbinary.ini

RUN /opt/bref/bin/pecl install mongodb
RUN echo "extension=mongodb.so" | tee -a /opt/bref/etc/php/config.d/mongodb.ini

RUN yes '' | /opt/bref/bin/pecl install redis
RUN echo "extension=redis.so" | tee -a /opt/bref/etc/php/config.d/redis.ini

RUN no '' | /opt/bref/bin/pecl install apcu
RUN echo "extension=apcu.so" | tee -a /opt/bref/etc/php/config.d/apcu.ini

RUN LD_LIBRARY_PATH= yum install -y libicu-devel

# https://github.com/ImageMagick/ImageMagick
# Required to build the imagick extension
ARG imagemagick
ENV VERSION_IMAGEMAGICK=${imagemagick}
COPY makefiles/imagemagick.mk ${DEPS}/imagemagick.mk
RUN /usr/bin/make -f ${DEPS}/imagemagick.mk

# https://github.com/mkoppanen/imagick
ARG imagick
ENV VERSION_IMAGICK=${imagick}
COPY makefiles/imagick.mk ${DEPS}/imagick.mk
RUN /usr/bin/make -f ${DEPS}/imagick.mk 
RUN echo "extension = imagick.so" | tee -a /opt/bref/etc/php/config.d/imagick.ini

# https://libmemcached.org/libMemcached.html
# Required to build the phpmemcached extension
ARG memcached
ENV VERSION_MEMCACHED=${memcached}
COPY makefiles/memcached.mk ${DEPS}/memcached.mk
RUN /usr/bin/make -f ${DEPS}/memcached.mk

# https:
ARG php_memcached
ENV VERSION_PHP_MEMCACED=${php_memcached}
COPY makefiles/php-memcached.mk ${DEPS}/php-memcached.mk
RUN /usr/bin/make -f ${DEPS}/php-memcached.mk 
RUN echo "extension = memcached.so" | tee -a /opt/bref/etc/php/config.d/php-memcached.ini