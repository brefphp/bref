The "loop" PHP runtime contains the PHP CLI binary compiled with the experimental *loop* (`php -L`) option. It does not include PHP-FPM or CGI.

This binary's location in the layer is `/opt/bin/php`. Since the `/opt/bin` directory is automatically added to `$PATH` on AWS Lambda you can simply run `php` directly and it will work.
