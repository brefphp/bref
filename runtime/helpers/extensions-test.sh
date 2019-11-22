#!/usr/bin/env bash

if php -m 2>&1 >/dev/null | grep -q 'Unable'; then
   php -m 2>&1 >/dev/null | grep 'Unable to load dynamic library'
   exit 1
fi
