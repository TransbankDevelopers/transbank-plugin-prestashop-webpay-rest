#!/usr/bin/env bash

if composer; then
    echo COMPOSER IS CURRENTLY INSTALLED
else
    sudo php -r "copy('https://composer.github.io/installer.sig', '/composer-setup.sig');"
    sudo php -r "copy('https://getcomposer.org/installer', '/composer-setup.php');"
    sudo php -r "if (hash_file('SHA384', '/composer-setup.php') === trim(file_get_contents('/composer-setup.sig'))) { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
    sudo php /composer-setup.php --install-dir=/usr/local/bin --filename=composer
    sudo php -r "unlink('/composer-setup.php');"
    sudo php -r "unlink('/composer-setup.sig');"
    echo COMPOSER HAS BEEN INSTALLED!
fi