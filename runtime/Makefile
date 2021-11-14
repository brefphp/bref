export TAG ?= al2-x86_64
export CPU ?= x86
export ROOT_DIR ?= $(shell pwd)/../
export AWS_PROFILE ?= deleugpn_brefphp

.SILENT: everything clean

# This command is designed for bref internal use only and will publish every image
# using the configured AWS_PROFILE. Most users will not want to use this option
# as this will distribute all layers to all regions.
everything:
	# Build (in parallel) the internal packages that will be copied into the layers
	docker-compose -f ./common/docker-compose.yml build --parallel

	# Clean up the folder before building all layers
	rm /tmp/bref-zip/ -rf

	# We build the layer first because we want the Docker Image to be properly tagged so that
	# later on we can push to Docker Hub.
	docker-compose build --parallel php74-function php80-function php81-function

	# After we build the layer successfully we can then zip it up so that it's ready to be uploaded to AWS.
	docker-compose build --parallel php74-zip-function php80-zip-function php81-zip-function

	# Repeat the same process for FPM
	docker-compose build --parallel php74-fpm php80-fpm php81-fpm
	docker-compose build --parallel php74-zip-fpm php80-zip-fpm php81-zip-fpm

	# By running the zip containers, the layers will be copied over to /tmp/bref-zip/
	docker-compose up php74-zip-function php80-zip-function php81-zip-function \
		php74-zip-fpm php80-zip-fpm php81-zip-fpm

	# This will clean up orphan containers
	docker-compose down

	# Upload the Function layers to AWS
	#TYPE=function PHP_VERSION=php74 docker-compose -f ./common/publish/docker-compose.yml up
	#TYPE=function PHP_VERSION=php80 docker-compose -f ./common/publish/docker-compose.yml up
	#TYPE=function PHP_VERSION=php81 docker-compose -f ./common/publish/docker-compose.yml up

	# Upload the FPM Layers to AWS
	#TYPE=fpm PHP_VERSION=php74 docker-compose -f ./common/publish/docker-compose.yml up
	#TYPE=fpm PHP_VERSION=php80 docker-compose -f ./common/publish/docker-compose.yml up
	#TYPE=fpm PHP_VERSION=php81 docker-compose -f ./common/publish/docker-compose.yml up

	# Transform /tmp/bref-zip/output.ini into layers.json
	#docker-compose -f common/utils/docker-compose.yml run parse

	# TODO: Docker Push to Docker Hub.
