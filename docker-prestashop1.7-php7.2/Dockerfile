FROM prestashop/prestashop:1.7-7.2-apache

# instala lo basico
RUN apt-get update && apt-get install -y nano

RUN apt-get install -y libmcrypt-dev && \
    pecl install mcrypt-1.0.1 && \
    docker-php-ext-enable mcrypt

#agrega un php.ini personalizado
ADD ./files/php.ini /usr/local/etc/php/php.ini

#agrega scripts de utilidad
ADD ./files/*.sh /var/www/html/
