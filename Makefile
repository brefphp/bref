.EXPORT_ALL_VARIABLES:

trigger_runtimes:
	aws codepipeline start-pipeline-execution --name bref-php-binary

runtime_build_status:
	aws codepipeline get-pipeline-state --name=bref-php-binary | jq ".stageStates[1].latestExecution.status"

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
	php utils/layers.json/update.php

test-stack:
	serverless deploy -c tests/serverless.tests.yml

.PHONY: website website-preview website-assets demo layers.json test-stack
