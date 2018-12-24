.EXPORT_ALL_VARIABLES:

BREF_VERSION := 0.3
REGION := us-east-2
PHP_VERSION := 7.2.12
PHP_VERSION_SHORT := 72

# Build the PHP runtimes
runtimes: runtime-default runtime-fpm runtime-loop
runtime-default:
	cd runtime/default && sh publish.sh
runtime-fpm:
	cd runtime/fpm && sh publish.sh
runtime-loop:
	cd runtime/loop && sh publish.sh

website-preview:
	couscous preview

.PHONY: runtimes runtime-default runtime-fpm runtime-loop website-preview
