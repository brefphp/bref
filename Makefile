.EXPORT_ALL_VARIABLES:

trigger_runtimes:
	aws codepipeline start-pipeline-execution --name bref-php-binary

runtime_build_status:
	aws codepipeline get-pipeline-state --name=bref-php-binary | jq ".stageStates[1].latestExecution.status"

# Deploy the demo functions
demo:
	serverless deploy

layers.json:
	php utils/layers.json/update.php

test-stack:
	serverless deploy -c tests/serverless.tests.yml

preview:
	cd website && make preview

.PHONY: demo layers.json test-stack
