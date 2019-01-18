The "console" layer is a layer that comes on top of the PHP runtime. It lets us execute console commands on lambda.

This layer overrides the `bootstrap` to execute CLI console commands (e.g. Symfony Console or Laravel Artisan).

Read more at [bref.sh/docs/runtimes/console.html](https://bref.sh/docs/runtimes/console.html).
