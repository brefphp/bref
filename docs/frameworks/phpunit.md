---
title: Serverless Testing with PHPUnit
currentMenu: frameworks
introduction: Run your unit tests as close to production environment
---

Running unit tests in an environment that is not close to the production environment might not bring as much confidence as expected from a test suite.
One way of visualizing this fact is to try and run a test suite using PHP 7.0 against a codebase that makes use of nullable type-hit. 
Of course, this example is extreme, but the sentiment remains: it is somewhat relevant to execute a test suite using the same php binary that will be running on production to cater for php version as well as required extesions.

For this reason, bref provides a Docker image containing the same php binary as the one inside the Lambda Layer.

## Usage

The following command assumes that you're executing it from the root folder of your project:

```
docker run -v $(pwd):/var/task -w /var/task -t bref/php-73 sh -c "php -r \"copy('https://getcomposer.org/installer', 'composer-setup.php');\" && php composer-setup.php && php composer.phar install && ./vendor/bin/phpunit"
```

## How it works?

When building the Lambda Layer, Bref uses a base image provided by Amazon containing the Amazon Linux. This is the same operating system that Lambda is using under the hood.
The PHP binary is then compiled from source code and packaged into a Lambda layer. 
Bref pushes the same Docker image used to make up the layer to Docker Hub, which means the exact same binary is being used.
After starting the container, the command will download and run Composer on the project in order to install all the dependencies, including the development dependencies to bring in the testing framework (e.g. PHPUnit).
