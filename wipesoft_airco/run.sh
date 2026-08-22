#!/bin/sh
set -eu

php /app/src/bridge.php &
php-fpm -D
exec nginx -g 'daemon off;'
