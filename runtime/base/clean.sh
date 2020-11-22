# ------------------------------------------
# This script cleans extra files from /opt
# to keep the layers as small as possible.
# ------------------------------------------

# Stop on error
set -e
# Treat unset variables and parameters as an error.
set -u

# Strip all the unneeded symbols from shared libraries to reduce size.
find /opt/bref -type f -name "*.so*" -exec strip --strip-unneeded {} \;
find /opt/bref -type f -name "*.a"|xargs rm
find /opt/bref -type f -name "*.la"|xargs rm
find /opt/bref -type f -name "*.dist"|xargs rm
find /opt/bref -type f -executable -exec sh -c "file -i '{}' | grep -q 'x-executable; charset=binary'" \; -print|xargs strip --strip-all

# Cleanup all the binaries we don't want.
find /opt/bref/bin -mindepth 1 -maxdepth 1 ! -name "php" ! -name "php-fpm" -exec rm {} \+
find /opt/bin -mindepth 1 -maxdepth 1 ! -name "php" ! -name "php-fpm" -exec rm {} \+

# Cleanup all the files we don't want either
# We do not support running pear functions in Lambda
rm -rf /opt/bref/lib/php/PEAR
rm -rf /opt/bref/share
rm -rf /opt/bref/include
rm -rf /opt/bref/php
rm -rf /opt/bref/{lib,lib64}/pkgconfig
rm -rf /opt/bref/{lib,lib64}/cmake
rm -rf /opt/bref/lib/xml2Conf.sh
find /opt/bref/lib/php -mindepth 1 -maxdepth 1 -type d -a -not -name extensions |xargs rm -rf
find /opt/bref/lib/php -mindepth 1 -maxdepth 1 -type f |xargs rm -rf
rm -rf /opt/bref/lib/php/test
rm -rf /opt/bref/lib/php/doc
rm -rf /opt/bref/lib/php/docs
rm -rf /opt/bref/tests
rm -rf /opt/bref/doc
rm -rf /opt/bref/docs
rm -rf /opt/bref/man
rm -rf /opt/bref/php/man
rm -rf /opt/bref/www
rm -rf /opt/bref/cfg
rm -rf /opt/bref/libexec
rm -rf /opt/bref/var
rm -rf /opt/bref/data
