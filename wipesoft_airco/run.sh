#!/bin/sh
set -eu

php-fpm -D
exec nginx -g 'daemon off;'

