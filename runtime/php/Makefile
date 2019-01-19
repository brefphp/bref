SHELL := /bin/bash

# Build the docker container that will be used to compile PHP and its extensions
compiler:
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

# Compile PHP and its extensions
build: compiler
	docker build -f ${PWD}/php.Dockerfile -t bref/runtime/php:latest $(shell helpers/docker_args.php php72) .

# Export the compiled PHP artifacts into zip files that can be uploaded as Lambda layers
distribution: build
	# Run the export script in the PHP container
	docker run --rm \
		--volume ${PWD}/layers:/layers:ro \
		--volume ${PWD}/../export:/export \
		--volume ${PWD}/export.sh:/export.sh:ro \
		bref/runtime/php:latest \
		/export.sh
