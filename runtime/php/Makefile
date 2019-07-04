SHELL := /bin/bash

# Build the docker container that will be used to compile PHP and its extensions
compiler: compiler.Dockerfile
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

# Compile PHP and its extensions
build: compiler
	docker build -f ${PWD}/php.Dockerfile -t bref/php-72:latest $(shell helpers/docker_args.sh versions.ini php72) .
	docker build -f ${PWD}/php-local.Dockerfile -t bref/php-72:dev --build-arg LAYER_IMAGE=bref/php-72:latest .
	docker build -f ${PWD}/php.Dockerfile -t bref/php-73:latest $(shell helpers/docker_args.sh versions.ini php73) .
	docker build -f ${PWD}/php-local.Dockerfile -t bref/php-73:dev --build-arg LAYER_IMAGE=bref/php-73:latest .

# Export the compiled PHP artifacts into zip files that can be uploaded as Lambda layers
distribution: build
	# Run the export script for PHP 7.2
	docker run --rm \
		--env PHP_SHORT_VERSION=72 \
		--volume ${PWD}/layers:/layers:ro \
		--volume ${PWD}/../export:/export \
		--volume ${PWD}/export.sh:/export.sh:ro \
		bref/php-72:latest \
		/export.sh
	# Run the export script for PHP 7.3
	docker run --rm \
		--env PHP_SHORT_VERSION=73 \
		--volume ${PWD}/layers:/layers:ro \
		--volume ${PWD}/../export:/export \
		--volume ${PWD}/export.sh:/export.sh:ro \
		bref/php-73:latest \
		/export.sh

publish: build
	docker push bref/php-72:latest
	docker push bref/php-73:latest
