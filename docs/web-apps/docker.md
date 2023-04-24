---
title: Docker
current_menu: web-docker
previous:
    link: local-development.html
    title: Local development for web apps
---

AWS Lambda supports running a Docker image, instead of running your application in the default Linux environment. We recommend Docker **as a last resort**, as it is less practical and usually comes with slightly worse cold starts. Yes, Docker is great and probably sounds familiar, but is often not worth it on Lambda.

You should consider deploying using Docker when:

- Your Lambda Function is [larger than 250MB when unzipped](../environment/storage.md)
- You reached the limit of 5 Lambda layers (e.g. for extra PHP extensions)
- You need resources installed locally (e.g. mysqldump)

> Note: this documentation page assumes that you have read about [web apps on Lambda](../runtimes/http.md) first.

## Docker Image

Bref helps you deploy to AWS Lambda using Docker by offering
out-of-the-box base images that are package for the Lambda environment.
Here is an example of a Docker image

```Dockerfile
FROM bref/php-80-fpm:2

COPY . /var/task

# Configure the handler file (the entrypoint that receives all HTTP requests)
CMD ["public/index.php"]
```

This Dockerfile outlines the 3 key aspects of Docker on Lambda:

- Base image compatible with Lambda Runtime
- Source code placed under `/var/task`
- CMD pointing to the entrypoint that will handle requests

You may also enable PHP extensions by pulling them from
[Bref Extensions](https://github.com/brefphp/extra-php-extensions)

```Dockerfile
FROM bref/php-80-fpm:2

COPY --from=bref/extra-redis-php-80:1 /opt /opt
COPY --from=bref/extra-gmp-php-80:1 /opt /opt

COPY . /var/task

CMD ["public/index.php"]
```

## Deployment

The Serverless Framework supports deploying Docker images to Lambda:

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

Instead of having a `handler` and a `runtime`, we'll declare an
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

## Filesystem

The filesystem for Docker on AWS Lambda is also readonly with
a limited disk space under `/tmp` for read/write. This folder
will always be empty when a new cold start happens. Avoid
writing content to `/tmp` in your Dockerfile because that
content will **not be available** for your Lambda function.

[Read more about file storage in Lambda](../environment/storage.md).

## Docker Registry

AWS Lambda only support AWS ECR as the source location for
Docker images. The Lambda service will use the image digest
as the unique identifier. This means that even if you overwrite
the exact same tag on ECR, your lambda will still run the previous
image code until you actually redeploy using the new image.
