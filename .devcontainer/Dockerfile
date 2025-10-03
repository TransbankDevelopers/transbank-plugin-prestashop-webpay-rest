FROM prestashop/prestashop:8.2.3-8.1-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    nano \
    vim \
    wget \
    sudo \
    bash-completion \
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

RUN groupadd -g 1000 vscode || true \
    && useradd -m -u 1000 -g 1000 -s /bin/bash vscode \
    && usermod -aG www-data vscode \
    && echo "vscode ALL=(ALL) NOPASSWD:ALL" >> /etc/sudoers

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/log/php && chown -R www-data:www-data /var/log/php

RUN ln -snf /usr/share/zoneinfo/America/Santiago /etc/localtime \
 && echo "America/Santiago" > /etc/timezone \
 && echo "date.timezone = America/Santiago" > /usr/local/etc/php/conf.d/00-timezone.ini

RUN printf '%s\n' \
  'log_errors=On' \
  'error_reporting=E_ALL' \
  'display_errors=Off' \
  'error_log=/var/log/php/error.log' \
  > /usr/local/etc/php/conf.d/99-logging.ini

RUN mkdir -p /var/www/html/var/log && chown -R www-data:www-data /var/www/html/var/log \
 && printf '%s\n' \
  '[www]' \
  'catch_workers_output = yes' \
  'php_admin_flag[log_errors] = on' \
  'php_admin_value[error_log] = /var/www/html/var/log/php_runtime.log' \
  > /usr/local/etc/php-fpm.d/zz-logging.conf

WORKDIR /workspace
