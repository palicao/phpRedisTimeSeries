#!/usr/bin/env bash

set -e

composer install --no-progress --prefer-dist --optimize-autoloader
./vendor/bin/phpunit --coverage-text
vendor/bin/psalm