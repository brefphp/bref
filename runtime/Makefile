# Publish the layers on AWS Lambda
publish: layers
	php publish.php

# Build the layers
layers: export/console.zip export/php-%.zip

# The PHP runtimes
export/php-%.zip:
	cd php && make distribution

# The console runtime
export/console.zip: console/bootstrap ../src/Runtime/LambdaRuntime.php
	rm -f export/console.zip
	cp ../src/Runtime/LambdaRuntime.php console/LambdaRuntime.php
	cd console && zip ../export/console.zip bootstrap LambdaRuntime.php
	rm console/LambdaRuntime.php
