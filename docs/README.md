# What is Bref and serverless?

<p class="text-lg">
Bref simplifies running PHP applications by taking advantage of the best of serverless.
</p>

Instead of trying to fit all needs, Bref is opinionated and brings the best tools together to provide a solution that works.

## An introduction to serverless

Serverless is a new approach to running applications where **we don't think about servers anymore**.

More specifically:

- we don't manage, update, patch, provision the servers
- we don't reserve or scale the servers, instead they are scaled automatically and transparently for us
- we don't pay for servers, instead we pay for what we actually use (e.g. execution time)

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

## Bref choices

Bref deploys applications to [AWS](https://aws.amazon.com).

The choice of this provider is deliberate: at the moment AWS is the leading hosting provider, it is ahead in the serverless space in terms of features, performances and reliability.

Bref uses [SAM](https://docs.aws.amazon.com/serverless-application-model/latest/developerguide/what-is-sam.html), AWS' official tool to configure and deploy serverless applications. SAM allows to stay up to date with AWS best practices as well as a deep integration with all other AWS tools.

Since **AWS Lambda** is AWS' FaaS service, Bref deploys PHP code to Lambda.

## Use cases

Bref and AWS Lambda can be used to run any kind of PHP application, including:

- websites
- APIs
- workers
- batch processes/scripts
- etc.

Bref aims to support any PHP framework as well.

If you are interested in real-world examples as well as cost analyses head over to the [Case Studies page](case-studies.md).

## Getting started

Get started with Bref by reading the [installation documentation](installation.md)
