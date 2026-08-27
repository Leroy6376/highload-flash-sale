#!/bin/sh

set -eu

mkdir -p \
    /var/www/bootstrap/cache \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs

chown -R www-data:www-data /var/www/bootstrap/cache /var/www/storage

exec "$@"
