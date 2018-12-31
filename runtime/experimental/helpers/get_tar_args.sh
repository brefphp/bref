#!/bin/bash
ext="${1##*.}"

if [ $ext == 'gz' ] || [ $ext == 'tgz' ]; then
	echo "xzC"
elif [ $ext == 'bz2' ]; then
    echo "xjC"
else
	echo "xJC"
fi