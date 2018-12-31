SHELL := /bin/bash

compiler:
	docker build -f ${PWD}/compiler.Dockerfile -t bref/runtime/compiler:latest .

shell/compiler:
	docker run --interactive --tty -v ${PWD}:/host --entrypoint /bin/bash -e "TERM=xterm-256color" bref/runtime/compiler:latest

libs: compiler
	docker build -f ${PWD}/libs.Dockerfile -t bref/runtime/libs:latest $(shell helpers/docker_args.php libs) .

shell/libs:
	docker run --interactive --tty -v ${PWD}:/host --entrypoint "/bin/bash" -e "TERM=xterm-256color" bref/runtime/libs:latest

php73: make_php73 distro export

make_php73: libs
	docker build -f ${PWD}/php.Dockerfile  -t bref/runtime/php:latest $(shell helpers/docker_args.php php73) .

shell/php73: 
	docker run --interactive --tty -v ${PWD}:/host --entrypoint "/bin/bash" -e "TERM=xterm-256color" bref/runtime/php:latest

php72: make_php72 distro export

make_php72: libs
	docker build -f ${PWD}/php.Dockerfile -t bref/runtime/php:latest $(shell helpers/docker_args.php php72) .

shell/php72: make_php72
	docker run --interactive --tty -v ${PWD}:/host --entrypoint "/bin/bash" -e "TERM=xterm-256color" bref/runtime/php:latest

php71: make_php71 distro export

make_php71:  libs
	docker build -f ${PWD}/php.Dockerfile -t bref/runtime/php:latest $(shell helpers/docker_args.php php71) .

shell/php71: make_php71
	docker run --interactive --tty -v ${PWD}:/host --entrypoint "/bin/bash" -e "TERM=xterm-256color" bref/runtime/php:latest

php70: make_php70 distro export

make_php70:  libs
	docker build -f ${PWD}/php.DockerfileMake -t bref/runtime/php:latest $(shell helpers/docker_args.php php70) .

shell/php70: make_php70
	docker run --interactive --tty -v ${PWD}:/host --entrypoint "/bin/bash" -e "TERM=xterm-256color" bref/runtime/php:latest

distro:
	docker build -f ${PWD}/export.Dockerfile -t bref/runtime/dist:latest .

shell/distro: 
	docker run --interactive --tty -v ${PWD}:/host --entrypoint "/bin/bash" -e "TERM=xterm-256color" bref/runtime/dist:latest

export: distro
	mkdir -p ${PWD}/exports
	rm -rf ${PWD}/exports/*.zip
	docker run --interactive --tty -v ${PWD}/exports:/export -e "TERM=xterm-256color" bref/runtime/dist:latest
	ls -al ${PWD}/exports

clean:
	docker system prune -f