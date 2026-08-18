.EXPORT_ALL_VARIABLES:

layers.json:
	php utils/layers.json/update.php

test-stack:
	serverless deploy -c tests/serverless.tests.yml

preview:
	cd website && make preview

.PHONY: layers.json test-stack
