---
title: Serverless Laravel applications
currentMenu: laravel
introduction: Learn how to deploy serverless Laravel applications on AWS Lambda using Bref.
---

This guide helps you run Laravel applications on AWS Lambda using Bref. These instructions are kept up to date to target the latest Laravel version.

A demo application is available on GitHub at [github.com/mnapoli/bref-laravel-demo](https://github.com/mnapoli/bref-laravel-demo).

## Installation

Assuming your are in existing Laravel project, let's install Bref via Composer:

```
composer require mnapoli/bref
```

Then let's create a `template.yaml` configuration file (at the root of the project) optimized for Laravel:

```
 artisan vendor:publish --tag bref-sam-template
```

## Configuration
You will need an S3 bucket to send the Function Package to in order for Cloudformation to consume it. Either use and existing bucket, or create a new one.
```sh
aws s3 mb s3://<bucket-name>
```

New edit your `.env` file and add:

```ini
BREF_NAME="<my-lambdas-name>"
BREF_S3_BUCKET="<bucket-name>"
```

*OPTIONAL:* If you would like to do more advanced configuration you may publish the `bref.config` to your Laravel `./config` directory and edit it as well.
```
 artisan vendor:publish --tag bref-configuration
```

Lastly, tell Bref to finalize the SAM template for you.

```
artisan bref:config-sam
```

## Usage
### Update Configuration
This command can be run at anytime to update the template if you change environment variables, routes, or other configuration options.
```
artisan bref:config-sam
```

### Package Project
This command will zip up your project and store it in the laravel `./storage` directory. It will also symlink `./storage/latest.zip` to the last zip package created.
```
artisan bref:config-sam
```
### Deploy Project
This command will deploy your project to SAM. The first time can take a moment. After this is run, your application should be up and running in AWS!
```
artisan bref:deploy
```

### Update Function Code
Doing a full deploy to test code changes can be an aggravation. Use this command to update the Lambda Function in AWS after you make code changes. However, if you make configuration changes, you will need to run 'bref:deploy` again.
```
artisan bref:update
```

### Local API Testing
If you have docker installed, and would like to test your code locally. This command will run your code in docker and give you a local testpoint to check it out in.
```
artisan bref:start-api
```

## Laravel Artisan

As you may have noticed, we define a function of type "Artisan" in `template.yaml`. That function is using the [Console runtime](/docs/runtimes/console.md), which lets us run Laravel Artisan on AWS Lambda.

To use it follow [the "Console" guide](/docs/runtimes/console.md).
