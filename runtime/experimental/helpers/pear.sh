#!/usr/bin/expect
spawn /opt/bref/bin/curl -k -o /tmp/go-pear.phar http://pear.php.net/go-pear.phar
expect eof
spawn /opt/bref/bin/php /tmp/go-pear.phar

expect "1-11, 'all' or Enter to continue:"
send "\r"
expect eof