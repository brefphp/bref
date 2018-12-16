The "fpm" PHP runtime contains the PHP CLI binary as well as the PHP-FPM binary.

Location of the binaries:

- `/opt/bin/php`
- `/opt/bin/php-fpm`

Since the `/opt/bin` directory is automatically added to `$PATH` on AWS Lambda you can simply run `php` or `php-fpm` directly and it will work.
