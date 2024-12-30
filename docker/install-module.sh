#!/bin/bash

set -eu

echo "* [webpay] Installing module webpay..."
su -s /bin/bash www-data -c "php /var/www/html/bin/console prestashop:module --no-interaction install webpay"
