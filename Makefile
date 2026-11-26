.EXPORT_ALL_VARIABLES:

test-stack:
	serverless deploy -c tests/serverless.tests.yml

preview:
	cd website && make preview

.PHONY: test-stack
