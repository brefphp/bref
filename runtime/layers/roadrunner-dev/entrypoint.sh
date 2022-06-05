#!/usr/bin/env sh

# Prepare rr-config file
if [ -f /var/task/.rr.yaml ]; then
  cp /var/task/.rr.yaml /tmp/octane/.rr.yaml
else
  mkdir /tmp/octane/
  touch /tmp/octane/.rr.yaml
fi

exec /opt/bin/php /var/task/artisan octane:start --server roadrunner --rr-config /tmp/octane/.rr.yaml --port 8000 --host 0.0.0.0
