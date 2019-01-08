SHELL := /bin/bash

compiler:
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

php73: make_php73 distro

make_php73: compiler
	docker build -f ${PWD}/php.Dockerfile  -t bref/runtime/php:latest $(shell helpers/docker_args.php php73) .

php72: make_php72 distro

make_php72: compiler
	docker build -f ${PWD}/php.Dockerfile -t bref/runtime/php:latest $(shell helpers/docker_args.php php72) .

distro:
	docker build -f ${PWD}/export.Dockerfile -t bref/runtime/dist:latest .
	docker run --name bref-export-container bref/runtime/dist:latest /bin/true
	docker cp  bref-export-container:/export .
	docker rm  bref-export-container
