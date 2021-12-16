FROM php:7-fpm as base
ENV COMPOSER_HOME /opt/composer

RUN apt-get update -qq \
    && apt-get install -y  \
        git \
        libicu-dev \
        p7zip-full \
    && rm -rf /var/lib/apt/lists/*
# Install required PHP extensions
RUN pecl install redis \
    && docker-php-ext-install \
      intl \
      gettext \
      pdo_mysql \
    && docker-php-ext-enable redis.so

# Install composer 2.1.14
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;" \
    && php composer-setup.php \
    && php -r "unlink('composer-setup.php');" \
    && mv composer.phar /usr/local/bin/composer \
    && mkdir -p $COMPOSER_HOME

# Install composer deps globally \
# It'll be useful for the production image sometime and acts as a cache for the dev env
COPY composer.json $COMPOSER_HOME/
COPY resources/ ./resources
RUN composer global install --no-dev --no-autoloader

VOLUME /media

FROM base as dev

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && composer global install --no-autoloader
