---
title: Performances
current_menu: performances
introduction: Performance tuning and optimizations for serverless PHP applications on AWS Lambda.
---

This article sums up what to expect in terms of performances and how to optimize serverless PHP applications. The benchmarks included in this page [are published on GitHub](https://github.com/brefphp/benchmarks).

## Overhead

### HTTP

The Bref [runtime for HTTP applications](/docs/runtimes/http.md) **does not add overhead to response times**.

Here are execution times for an empty PHP application:

|                  | 128M  | 512M | 1024M | 2048M |
|------------------|------:|-----:|------:|------:|
| Execution time   |  10ms |  1ms |   1ms |   1ms |

Unless we use a particularly slow lambda (see the next section, 128M is not recommended), 1ms is the same execution time when PHP runs with Apache or Nginx on a classic server.

We can see the same result with a "Hello world" written in Symfony (4ms being the minimum execution time of the framework):

|                  | 128M  | 512M | 1024M | 2048M |
|------------------|------:|-----:|------:|------:|
| Execution time   |  58ms |  4ms |   4ms |   4ms |

### Functions

The Bref [runtime for PHP functions](/docs/runtimes/function.md) (non-HTTP applications) adds a small overhead:

|                  | 128M  | 512M | 1024M | 2048M |
|------------------|------:|-----:|------:|------:|
| Execution time   | 175ms | 35ms |  16ms |  13ms |

Since this runtime is often used in asynchronous scenarios (processing queue messages for example), it is often negligible.

## CPU power and memory size

The main factor that affects performances is a lambda's memory size. Indeed, [the amount of memory is proportional to the CPU power available](https://docs.aws.amazon.com/lambda/latest/dg/resource-model.html).

In other words, **more memory means a more powerful CPU**. A 1024M lambda has a CPU 2 times more powerful than a 512M lambda.

From 64M to 1,792M, applications run with one CPU (1,792M gives 1 full CPU). From 1,856M to 3,008M, applications run with 2 CPU. However, since PHP is single threaded and one lambda handles only 1 request at a time, using 2 CPU usually does not provide any benefit.

**It is recommended to use 1024M** for PHP applications, or at least to start with that. This is what Serverless configures by default.

To customize the amount of memory, set the `memory` option in `serverless.yml`:

```yaml
functions:
    foo:
        handler: index.php
        # ...
        memory: 512 # set to 512M instead of 1024M (the default)
```

In the benchmark below, we run [PHP's official `bench.php` script](https://github.com/php/php-src/blob/master/Zend/bench.php). This script is CPU-intensive.

|                  | 128M  | 512M | 1024M | 2048M |
|------------------|------:|-----:|------:|------:|
| Execution time   |  5.7s | 1.4s | 0.65s | 0.33s |

For comparison, `bench.php` runs in 1.3s on a 512M [Digital Ocean](https://www.digitalocean.com/) server, in 0.8s on a 2.8Ghz i7 and in 0.6s on a 3.2Ghz i5. It is safe to say that a 1024M lambda provides a powerful CPU.

### Costs

AWS Lambda bills the number of events + the execution time. The more memory configured for a lambda, [the more expensive is the execution time](https://aws.amazon.com/lambda/pricing/).

It might be tempting to lower the memory to save money. However a function might run slower on a smaller lambda, cancelling the costs savings. Here is an example where both lambdas cost the same thing:

- a function runs in 400ms on a 512M lambda
- the same function runs in 200ms on a 1024M lambda because of the faster CPU

In general, use smaller and slower lambdas only if speed is not important at all.
