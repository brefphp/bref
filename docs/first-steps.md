---
title: First steps
current_menu: first-steps
introduction: First steps to discover Bref and deploy your first PHP application on AWS Lambda.
previous:
    link: /docs/installation.html
    title: Installation
next:
    link: /docs/runtimes/
    title: What are runtimes?
---

This guide will help you deploy your first PHP application on AWS Lambda. For simplicity, we will not be using a PHP framework yet.

Before getting started make sure you have [installed Bref and the required tools](installation.md) first.

## Initializing the project

Starting in an empty directory, install Bref using Composer:

```
composer require bref/bref
```

Then let's start by initializing the project by running:

```
vendor/bin/bref init
```

Accept all the defaults by pressing "Enter". The following files will be created in your project:

- `index.php` contains the code of your application
- `serverless.yml` contains the configuration for deploying on AWS

You are free to edit the code in `index.php`, but for now let's keep it simple: we want to run `index.php` on Lambda first.

## Deployment

To deploy, let's run:

```bash
serverless deploy
```

Once the command finishes, it should print a URL like this one:

```sh
https://3pjp2yiw97.execute-api.us-east-1.amazonaws.com
```

Open this URL and you should see your application: `index.php` is running on Lambda!

🎉 congrats on creating your first serverless application!

To learn more about deployments, head over the [Deployment guide](deploy.md).

## What's next?

Now that you have deployed a simple PHP web app, you can [learn more about runtimes](/docs/runtimes/). That will help you deploy HTTP and console applications.
