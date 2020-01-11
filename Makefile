.EXPORT_ALL_VARIABLES:

# Build the PHP runtimes
runtimes:
	cd runtime ; make publish

# Build all docker images
docker-images:
	cd runtime ; make docker-images

# Publish doocker images
publish-docker-images: docker-images
    # Make sure we have defined the docker tag
	(test $(DOCKER_TAG)) && echo "Tagging images with \"${DOCKER_TAG}\"" || echo "You have to define environemnt variable DOCKER_TAG"
	test $(DOCKER_TAG)

	for image in \
	  "bref/php-72" "bref/php-72-fpm" "bref/php-72-fpm-dev" \
	  "bref/php-73" "bref/php-73-fpm" "bref/php-73-fpm-dev" \
	  "bref/php-74" "bref/php-74-fpm" "bref/php-74-fpm-dev" \
	  "bref/build-php-72" \
	  "bref/build-php-73" \
	  "bref/build-php-74" \
	  "bref/fpm-dev-gateway"; \
	  do \
      docker tag $$image:latest $$image:${DOCKER_TAG} ; \
      docker push $$image ; \
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
	cd website && npx tailwind build template/styles.css -o template/output.css
website/node_modules: website/package.json website/package-lock.json
	cd website && npm install

# Deploy the demo functions
demo:
	serverless deploy

layers.json:
	php runtime/layers/layer-list.php

.PHONY: runtimes website website-preview website-assets demo layers.json
