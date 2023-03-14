.EXPORT_ALL_VARIABLES:

trigger_runtimes:
	aws codepipeline start-pipeline-execution --name bref-php-binary

runtime_build_status:
	aws codepipeline get-pipeline-state --name=bref-php-binary | jq ".stageStates[1].latestExecution.status"

# Build the PHP runtimes
runtimes:
	cd runtime ; make publish

# Build all docker images
docker-images:
	cd runtime ; make docker-images

# Publish doocker images
publish-docker-images: docker-images
    # Make sure we have defined the docker tag
	(test $(DOCKER_TAG)) && echo "Tagging images with \"${DOCKER_TAG}\"" || echo "You have to define environment variable DOCKER_TAG"
	test $(DOCKER_TAG)

	for image in \
	  "bref/php-73" "bref/php-73-fpm" "bref/php-73-console" "bref/php-73-fpm-dev" \
	  "bref/php-74" "bref/php-74-fpm" "bref/php-74-console" "bref/php-74-fpm-dev" \
	  "bref/php-80" "bref/php-80-fpm" "bref/php-80-console" "bref/php-80-fpm-dev" \
	  "bref/php-81" "bref/php-81-fpm" "bref/php-81-console" "bref/php-81-fpm-dev" \
	  "bref/php-82" "bref/php-82-fpm" "bref/php-82-console" "bref/php-82-fpm-dev" \
	  "bref/build-php-73" \
	  "bref/build-php-74" \
	  "bref/build-php-80" \
	  "bref/build-php-81" \
	  "bref/build-php-82" \
	  "bref/fpm-dev-gateway"; \
	do \
		docker image tag $$image:1 $$image:${DOCKER_TAG} ; \
		docker image push --all-tags $$image ; \
	done

# Generate and deploy the production version of the website using http://couscous.io
website:
	# See http://couscous.io/
	couscous generate
	netlify deploy --prod --dir=.couscous/generated
website-staging:
	couscous generate
	netlify deploy --dir=.couscous/generated

# Run a local preview of the website using http://couscous.io
website-preview:
	couscous preview

website-assets: website/template/output.css
website/template/output.css: website/node_modules website/template/styles.css website/tailwind.config.js
	cd website && NODE_ENV=production npx tailwind build template/styles.css -o template/output.css
website/node_modules: website/package.json website/package-lock.json
	cd website && npm install

# Deploy the demo functions
demo:
	serverless deploy

layers.json:
	php runtime/layers/layer-list.php

test-stack:
	serverless deploy -c tests/serverless.tests.yml

changelog:
	docker run -it --rm -v "$(pwd)":/usr/local/src/your-app ferrarimarco/github-changelog-generator --user brefphp --project bref --output= --unreleased-only --token=$$GITHUB_TOKEN_READ --no-issues --usernames-as-github-logins --no-verbose

# http://amazon-linux-2-packages.bref.sh/
amazonlinux-package-list:
	docker run --rm -it --entrypoint= public.ecr.aws/lambda/provided:al2 yum list --quiet --color=never > index.html
	aws s3 cp index.html s3://amazon-linux-2-packages.bref.sh/ --content-type=text/plain
	rm index.html

getcomposer:
	./runtime/helpers/install-composer.sh

.PHONY: runtimes website website-preview website-assets demo layers.json test-stack changelog
