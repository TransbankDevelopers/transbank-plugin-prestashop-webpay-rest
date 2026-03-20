FROM prestashop/prestashop:9.0.0-8.2

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

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer


RUN ln -snf /usr/share/zoneinfo/America/Santiago /etc/localtime \
 && echo "America/Santiago" > /etc/timezone \
 && echo "date.timezone = America/Santiago" > /usr/local/etc/php/conf.d/00-timezone.ini

RUN printf '%s\n' \
  'log_errors=On' \
  'error_reporting=E_ALL' \
  'display_errors=Off' \
  'error_log=/var/log/apache2/php-error.log' \
  > /usr/local/etc/php/conf.d/99-logging.ini

RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

WORKDIR /workspace

COPY ./scripts/setup-module.sh /tmp/pre-install-scripts/
RUN chmod +x /tmp/pre-install-scripts/*.sh

COPY ./scripts/install-module.sh /tmp/init-scripts/
RUN chmod +x /tmp/init-scripts/*.sh

CMD ["sh", "-c", "rm -f ./install.lock && /tmp/docker_run.sh"]

RUN git config --global --add safe.directory /workspace
