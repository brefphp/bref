---
title: Docker
current_menu: web-docker
previous:
    link: docs/web-apps/local-development.html
    title: Local development for web apps
---

AWS Lambda support running a Docker Container as a handler
for your function. This alternative allows for lambda functions
with up to 10GB to be deployed, as opposed to 250MB for non-docker.
Another reason to choose this approach is if you need more than 5
PHP extensions. Installing/Enabling multiple extensions on Docker
is easier and will avoid hitting the limit of 5 layers per function.

## Docker Image

Bref helps you deploy Docker to AWS Lambda by offering
out-of-the-box base images that are package for the Lambda environment.
Here is an example of a Docker image

```Dockerfile
FROM composer:2 as installation

WORKDIR /app

RUN composer require bref/bref

FROM bref/php-80-fpm

COPY --from=bref/extra-redis-php-80:0.10 /opt/bref-extra /opt/bref-extra

COPY --from=bref/extra-gmp-php-80:0.10 /opt/bref-extra /opt/bref-extra
COPY --from=bref/extra-gmp-php-80:0.10 /opt/bref/ /opt/bref

COPY --from=installation /app/ /var/task/

RUN echo "<?php echo 'Hello from Docker Bref!';" >> /var/task/public/index.php

CMD ["public/index.php"]
```

The key structure of a Dockerfile for lambda consist of a base
image compatible with AWS Lambda (e.g. `bref/php-80-fpm`) and
the `CMD` pointing to the entrypoint of your project.

Another important consideration is to pack your application within
the `/var/task` folder. Bref will automatically look for an autoload
file at `/var/task/vendor/autoload.php`.

## Deployment

To deploy a Docker-based Lambda we'll use the Serverless Framework.
Here is an example of a template

```yaml
service: bref-with-docker

provider:
  name: aws
  ecr:
    images:
      hello-world:
        path: ./

functions:
  hello:
    image:
      name: hello-world
    events:
      - httpApi: '*'
```

Instead of having a `handler` and a `layer`, we'll declare an
`image`. In the `provider` block, we'll declare Docker images
that we want to build and deploy.

When running `serverless deploy`, the framework will:

- Build the Docker images according to their specified `path`
- Create an ECR Repository called `serverless-{service}-{env}`
- Authenticate against your ECR Account
- Push the newly built Docker Image
- Deploy the Lambda Function pointing to the Docker Image

When the deployment finishes, your lambda is ready to be
invoked from your API Gateway address.