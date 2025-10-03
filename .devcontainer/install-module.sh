#!/bin/bash

set -eu

echo "* [webpay] Configuring Timezone..."
su -s /bin/bash www-data -c "php /var/www/html/bin/console prestashop:config set --no-interaction PS_TIMEZONE --value=America/Santiago"

echo "* [webpay] Configuring My carrier (free, South America)..."

su -s /bin/bash www-data -c "php /var/www/html/bin/console dbal:run-sql \"UPDATE ps_carrier SET active=1, is_free=1 WHERE name='My carrier' AND deleted=0\""

su -s /bin/bash www-data -c "php /var/www/html/bin/console dbal:run-sql \"INSERT IGNORE INTO ps_carrier_zone (id_carrier, id_zone) SELECT c.id_carrier, z.id_zone FROM ps_carrier c JOIN ps_zone z ON z.name='South America' WHERE c.name='My carrier' AND c.deleted=0\""

echo "* [webpay] Installing module webpay..."
su -s /bin/bash www-data -c "php /var/www/html/bin/console prestashop:module --no-interaction install webpay"
