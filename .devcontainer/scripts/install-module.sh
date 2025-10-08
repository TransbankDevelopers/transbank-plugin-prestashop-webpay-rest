#!/bin/bash

set -eu

echo "* [Prestashop] Configuring Timezone..."
su -s /bin/bash www-data -c "php /var/www/html/bin/console prestashop:config set --no-interaction PS_TIMEZONE --value=America/Santiago"

echo "* [Prestashop] Configuring My carrier (free, South America)..."

su -s /bin/bash www-data -c "php /var/www/html/bin/console dbal:run-sql \"UPDATE ps_carrier SET active=1, is_free=1 WHERE name='My carrier' AND deleted=0\""

su -s /bin/bash www-data -c "php /var/www/html/bin/console dbal:run-sql \"INSERT IGNORE INTO ps_carrier_zone (id_carrier, id_zone) SELECT c.id_carrier, z.id_zone FROM ps_carrier c JOIN ps_zone z ON z.name='South America' WHERE c.name='My carrier' AND c.deleted=0\""

echo "* [Prestashop] Creating test customer..."

TEST_EMAIL="test.user@example.com"
TEST_FIRSTNAME="Test"
TEST_LASTNAME="User"
TEST_PASSWORD_PLAIN="Password123!"
TEST_ADDRESS1="Av. Demo 123"
TEST_CITY="Santiago"
TEST_POSTCODE="7500000"
TEST_PHONE="12345678"

TEST_PASS_HASH="$(php -r 'echo password_hash(getenv("P") ?: "Password123!", PASSWORD_BCRYPT);' P="$TEST_PASSWORD_PLAIN")"

mysql -h db -uprestashop -pprestashop123 prestashop <<SQL
-- Crear cliente si no existe (email único)
INSERT INTO ps_customer (
  id_shop_group, id_shop, id_gender, id_default_group, id_lang,
  firstname, lastname, email, passwd, secure_key,
  active, is_guest, newsletter, optin, date_add, date_upd
)
SELECT
  1, 1, 1, 3, 1,
  '$TEST_FIRSTNAME', '$TEST_LASTNAME', '$TEST_EMAIL', '$TEST_PASS_HASH', MD5(RAND()),
  1, 0, 0, 0, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM ps_customer WHERE email = '$TEST_EMAIL' LIMIT 1);

-- Obtener id del cliente
SET @cid := (SELECT id_customer FROM ps_customer WHERE email = '$TEST_EMAIL' LIMIT 1);

-- Asegurar pertenencia al grupo "Customer" (id_group=3)
INSERT IGNORE INTO ps_customer_group (id_customer, id_group) VALUES (@cid, 3);

-- Obtener país Chile (fallback a 1 si no existe)
SET @id_country := (
  SELECT c.id_country
  FROM ps_country_lang cl
  JOIN ps_country c ON c.id_country = cl.id_country
  WHERE cl.name = 'Chile' LIMIT 1
);
SET @id_country := IFNULL(@id_country, 1);

-- Crear dirección si el cliente no tiene alguna
INSERT INTO ps_address (
  id_customer, id_country, alias, firstname, lastname,
  address1, city, postcode, phone, active, date_add, date_upd
)
SELECT
  @cid, @id_country, 'Home', '$TEST_FIRSTNAME', '$TEST_LASTNAME',
  '$TEST_ADDRESS1', '$TEST_CITY', '$TEST_POSTCODE', '$TEST_PHONE', 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM ps_address WHERE id_customer = @cid LIMIT 1);
SQL

echo "* [webpay] Installing module webpay..."
su -s /bin/bash www-data -c "php /var/www/html/bin/console prestashop:module --no-interaction install webpay"
