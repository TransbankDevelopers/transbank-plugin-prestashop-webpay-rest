#!/bin/bash

set -eu

echo "* [webpay] Installing dependencies for webpay..."

cd webpay
composer install --no-dev
composer update --no-dev
