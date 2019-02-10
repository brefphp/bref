---
title: What is Bref and serverless?
currentMenu: what-is-bref
introduction: An introduction to what serverless and Bref can offer for PHP applications.
next:
    link: /docs/installation.html
    title: Installation
---

## Why serverless?

Serverless replaces the traditional approaches to running applications. With serverless:

- We don't manage, update, configure, provision servers or containers,
- We don't reserve or scale servers or containers, instead they are scaled automatically and transparently for us,
- We don't pay for fixed resources, instead we pay for what we actually use (e.g. execution time).

**Serverless can provide more scalable, affordable and reliable architectures for less effort.**

Serverless includes services like storage as a service, database as a service, message queue as a service, etc. One service in particular is interesting for us developers: *Function as a Service* (FaaS).

FaaS is a way to run code where the hosting provider takes care of setting up everything, keeping the application available 24/7, scaling it up and down and we are only charged *while the code is actually executing*.

## Why Bref?

<p class="text-xl">
Bref aims to make running PHP applications simple.
</p>

To reach that goal, Bref takes advantage of serverless technologies. However, while serverless is promising, there are many choices to make, tools to build and best practices to figure out.

Bref's approach is to:

- **simplify problems by removing choices**

    *instead of trying to address every need*
- **provide simple and familiar solutions**

    *instead of aiming for powerful custom solutions*
- **empower by sharing knowledge**

    *instead of hiding too much behind leaky abstractions*

### What is Bref

Bref (which means "brief" in french) comes as a Composer package and helps you deploy PHP applications to [AWS](https://aws.amazon.com) and run them on [AWS Lambda](https://aws.amazon.com/lambda/).

Bref provides:

- documentation
- PHP runtimes for AWS Lambda
- deployment tooling
- PHP frameworks integration

The choice of AWS as serverless provider is deliberate: at the moment AWS is the leading hosting provider, it is ahead in the serverless space in terms of features, performances and reliability.

Bref uses [SAM](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/what-is-sam.html), AWS' official tool to configure and deploy serverless applications. SAM allows to stay up to date with AWS best practices as well as a deep integration with all other AWS tools.

## Use cases

Bref and AWS Lambda can be used to run many kind of PHP application, for example:

- APIs
- workers
- batch processes/scripts
- websites

Bref aims to support any PHP framework as well.

If you are interested in real-world examples as well as cost analyses head over to the [**Case Studies** page](case-studies.md).

## Getting started

Get started with Bref by reading the [installation documentation](installation.md).
