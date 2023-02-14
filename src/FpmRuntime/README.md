This repository contains the PHP-FPM runtime code ([learn more about Bref runtimes](https://bref.sh/docs/runtimes/)).

This code is included in the `fpm` Lambda runtime provided by Bref (see https://github.com/brefphp/aws-lambda-layers for the code that builds these layers using Docker). It basically turns HTTP Lambda events into PHP-FPM requests.

It is stored in a GitHub repository separate from Bref because it is not used by Bref or by the application code (it runs as the Lambda runtime only). Additionally, the FPM runtime runs in a separate process from the application code, so we can don't have dependency constraints between application code and this code. As such, this code is bundled in the runtime. The benefit is that installing Bref in your project doesn't install all this code, nor its dependencies.
