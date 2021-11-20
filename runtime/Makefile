export TAG ?= al2-x86_64
export CPU ?= x86
export ROOT_DIR ?= $(shell pwd)/../
#export AWS_PROFILE ?= deleugpn_brefphp

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

	# Add executable permission to the publish script
	chmod +x ./common/publish/publish.sh

	# Upload the Function layers to AWS
	TYPE=function PHP_VERSION=php74 $(MAKE) -j7 publish
	TYPE=function PHP_VERSION=php80 $(MAKE) -j7 publish
	TYPE=function PHP_VERSION=php81 $(MAKE) -j7 publish

	# Upload the FPM Layers to AWS
	TYPE=fpm PHP_VERSION=php74 $(MAKE) -j7 publish
	TYPE=fpm PHP_VERSION=php80 $(MAKE) -j7 publish
	TYPE=fpm PHP_VERSION=php81 $(MAKE) -j7 publish

	# Transform /tmp/bref-zip/output.ini into layers.json
	docker-compose -f common/utils/docker-compose.yml run parse
	cp /tmp/bref-zip/layers.${CPU}.json ./../


# Here we're only tagging the latest images. This process is executed when a merge to
# master happens. We're using the same images that we built for the layers and
# publishing them on Docker Hub. When a Release Tag is created, GitHub Actions
# will be used to download the latest images, tag them with the version number
# and reupload them with the right tag.
docker-hub:
	# Temporarily creating aliases of the Docker images so that I can push to my own account
	docker tag bref/x86-php74-function breftest/x86-php74-function
	docker tag bref/x86-php80-function breftest/x86-php80-function
	docker tag bref/x86-php81-function breftest/x86-php81-function
	docker tag bref/x86-php74-fpm breftest/x86-php74-fpm
	docker tag bref/x86-php80-fpm breftest/x86-php80-fpm
	docker tag bref/x86-php81-fpm breftest/x86-php81-fpm

	# Backward compatible tags
	#TODO: change breftest/ to bref/
	docker tag bref/x86-php74-function breftest/php-74
	docker tag bref/x86-php80-function breftest/php-80
	docker tag bref/x86-php81-function breftest/php-81
	docker tag bref/x86-php74-fpm breftest/php-74-fpm
	docker tag bref/x86-php80-fpm breftest/php-80-fpm
	docker tag bref/x86-php81-fpm breftest/php-81-fpm

	$(MAKE) -j2 docker-hub-push-all


docker-hub-push-all: docker-hub-push-function docker-hub-push-fpm

docker-hub-push-function:
	#TODO: change breftest/ to bref/
	docker push breftest/x86-php74-function
	docker push breftest/x86-php80-function
	docker push breftest/x86-php81-function

	# Backward compatibility
	docker push breftest/php-74
	docker push breftest/php-81
	docker push breftest/php-81

docker-hub-push-fpm:
	#TODO: change breftest/ to bref/
	docker push breftest/x86-php74-fpm
	docker push breftest/x86-php80-fpm
	docker push breftest/x86-php81-fpm

	# Backward compatibility
	docker push breftest/php-74-fpm
	docker push breftest/php-81-fpm
	docker push breftest/php-81-fpm
##########################################################################################

# This command is designed for parallel execution of layer publishing.
# When we do `make publish -j7`, make will execute each command defined
# here in parallel.
publish: america-1 america-2 europe-1 europe-2 asia-1 asia-2 miscellaneous

america-1:
	REGION=us-east-1 ./common/publish/publish.sh #US East (N. Virginia)
	REGION=us-east-2 ./common/publish/publish.sh #US East (Ohio)
	REGION=us-west-1 ./common/publish/publish.sh #US West (N. California)

america-2:
	REGION=us-west-2 ./common/publish/publish.sh #US West (Oregon)
	REGION=ca-central-1 ./common/publish/publish.sh #Canada (Central)
	REGION=sa-east-1 ./common/publish/publish.sh #South America (São Paulo)

europe-1:
	REGION=eu-west-1 ./common/publish/publish.sh #Europe (Ireland)
	REGION=eu-west-2 ./common/publish/publish.sh #Europe (London)
	REGION=eu-west-3 ./common/publish/publish.sh #Europe (Paris)

europe-2:
	REGION=eu-north-1 ./common/publish/publish.sh #Europe (Stockholm)
	REGION=eu-south-1 ./common/publish/publish.sh #Europe (Milan)
	REGION=eu-central-1 ./common/publish/publish.sh #Europe (Frankfurt)

asia-1:
	REGION=ap-east-1 ./common/publish/publish.sh #Asia Pacific (Hong Kong)
	REGION=ap-south-1 ./common/publish/publish.sh #Asia Pacific (Mumbai)
	REGION=ap-southeast-1 ./common/publish/publish.sh #Asia Pacific (Singapore)

asia-2:
	REGION=ap-northeast-1 ./common/publish/publish.sh #Asia Pacific (Tokyo)
	REGION=ap-northeast-3 ./common/publish/publish.sh #Asia Pacific (Osaka)
	REGION=ap-northeast-2 ./common/publish/publish.sh #Asia Pacific (Seoul)

miscellaneous:
	REGION=af-south-1 ./common/publish/publish.sh #Africa (Cape Town)
	REGION=me-south-1 ./common/publish/publish.sh #Middle East (Bahrain)
	REGION=ap-southeast-2 ./common/publish/publish.sh #Asia Pacific (Sydney)