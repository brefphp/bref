---
title: What is Bref and serverless?
currentMenu: what-is-bref
---

<p class="text-lg">
Serverless replaces the traditionnal approaches to deploying and running applications. It provides more scalable, affordable and reliable architectures. However it is not always easy to get things right.
</p>

<p class="text-lg">
<strong>Bref's goal is to make serverless simple for PHP projects.</strong>
</p>

It does so by selecting the best tools, building those that are missing and providing a complete documentation.

Bref comes as a Composer package and uses 3rd party tools like [AWS SAM](https://github.com/awslabs/aws-sam-cli) to deploy on [AWS Lambda](https://aws.amazon.com/lambda/).

## Why serverless?

Serverless is a new approach to running applications where **we don't think about servers anymore**.

- We don't manage, update, patch, provision servers or containers,
- We don't reserve or scale servers or containers, instead they are scaled automatically and transparently for us,
- We don't pay for fixed resources, instead we pay for what we actually use (e.g. execution time).

This approach can bring several advantages:

- save time managing servers and deployment processes
- more scalable applications
- cost savings

Serverless includes services like storage as a service, database as a service, message queue as a service, etc. One service in particular is interesting for us developers: **Function as a Service** (FaaS).

FaaS is a way to run code where the hosting provider takes care of setting up everything, keeping the application available 24/7, scaling it up and down and we are only charged _while the code is actually executing_.

## Why Bref?

Since serverless technologies are spreading, using them becomes harder as there are more and more choices to consider.

On top of that PHP is often not natively supported and few resources exist to help us.

Bref is here to provide the missing tools, explain, document and help us get started in a good direction.

### Bref choices

Bref deploys applications to [AWS](https://aws.amazon.com).

The choice of this provider is deliberate: at the moment AWS is the leading hosting provider, it is ahead in the serverless space in terms of features, performances and reliability.

Bref uses [SAM](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/what-is-sam.html), AWS' official tool to configure and deploy serverless applications. SAM allows to stay up to date with AWS best practices as well as a deep integration with all other AWS tools.

Since **AWS Lambda** is AWS' FaaS service, Bref deploys PHP code to Lambda.

## Use cases

Bref and AWS Lambda can be used to run any kind of PHP application, for example:

- websites
- APIs
- workers
- batch processes/scripts

Bref aims to support any PHP framework as well.

If you are interested in real-world examples as well as cost analyses head over to the [**Case Studies** page](case-studies.md).

## Getting started

Get started with Bref by reading the [installation documentation](installation.md).
