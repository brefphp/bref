export REGION ?= eu-west-1
export AWS_PROFILE ?= deleugpn_brefphp

everything:
	$(MAKE) build
	LAYER_NAME=console $(MAKE) -C ../../common/publish/ publish

build:
	docker-compose build console-pkg
	docker-compose run console-zip
	LAYER_NAME=console docker-compose -f ../../common/publish/docker-compose.yml run publish