.EXPORT_ALL_VARIABLES:

# Build the PHP runtimes
runtimes:
	cd runtime && make publish

# Generate and deploy the production version of the website using http://couscous.io
website:
	# See http://couscous.io/
	couscous generate
	netlify deploy --prod --dir=.couscous/generated

# Run a local preview of the website using http://couscous.io
website-preview:
	couscous preview

website-assets: website/template/output.css
website/template/output.css: website/node_modules website/template/styles.css website/tailwind.js
	./website/node_modules/.bin/tailwind build website/template/styles.css -c website/tailwind.js -o website/template/output.css
website/node_modules:
	yarn install

# Deploy the demo functions
demo:
	rm -rf .couscous
	rm -f runtime/export/*.zip
	sam package \
		--region us-east-2 \
		--template-file template.yaml \
		--output-template-file output.yaml \
		--s3-bucket bref-demo-us-east-2
	sam deploy \
		--region us-east-2 \
		--template-file output.yaml \
		--stack-name bref-demo \
 		--capabilities CAPABILITY_IAM

layers.json:
	php runtime/layer-list.php

.PHONY: runtimes website website-preview website-assets demo
