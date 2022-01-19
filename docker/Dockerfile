FROM debian:stable
LABEL maintainer="lachlan-00"

ENV DEBIAN_FRONTEND=noninteractive

RUN     apt-get -q -q update \
    &&  apt-get -q -q -y install --no-install-recommends \
          software-properties-common \
          wget \
    &&  apt-add-repository contrib \
    &&  apt-add-repository non-free \
    &&  apt-get -q -q update \
    &&  apt-get -q -q -y install --no-install-recommends libdvd-pkg \
    &&  dpkg-reconfigure libdvd-pkg \
    &&  apt-get -qq install apt-transport-https lsb-release ca-certificates curl \
    &&  wget -q -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg \
    &&  sh -c 'echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list' \
    &&  apt-get update \
    &&  apt-get -qq install --no-install-recommends \
          apache2 \
          build-essential \
          cron \
          ffmpeg \
          flac \
          git \
          gosu \
          inotify-tools \
          lame \
          libavcodec-extra \
          libev-libevent-dev \
          libicu-dev \
          libfaac-dev \
          libmp3lame-dev \
          libtheora-dev \
          libvorbis-dev \
          libvpx-dev \
          logrotate \
          make \
          p7zip-full \
          php8.0 \
          php8.0-curl \
          php8.0-dev \
          php8.0-gd \
          php8.0-intl \
          php8.0-ldap \
          php8.0-mysql \
          php8.0-xml \
          php8.0-zip \
          php-pear \
          pwgen \
          supervisor \
          vorbis-tools \
          zip \
          unzip \
    &&  update-alternatives --set php /usr/bin/php8.0 \
    &&  update-alternatives --set phpize /usr/bin/phpize8.0 \
    &&  update-alternatives --set php-config /usr/bin/php-config8.0 \
    &&  phpenmod -v 8.0 xml \
    &&  rm -rf /var/www/html/* /etc/apache2/sites-enabled/* /var/lib/apt/lists/* \
    &&  mkdir -p /var/log/ampache \
    &&  chown -R www-data:www-data /var/log/ampache \
    &&  chmod 775 /var/log/ampache \
    &&  chown -R www-data:www-data /media \
    &&  chmod -R 775 /media \
    &&  chown -R www-data:www-data /var/www \
    &&  chmod -R 775 /var/www \
    &&  ln -s /etc/apache2/sites-available/001-ampache.conf /etc/apache2/sites-enabled/ \
    &&  a2enmod rewrite \
    &&  apt-get -qq purge \
          libdvd-pkg \
          lsb-release \
          software-properties-common \
          unzip \
    &&  apt-get -qq autoremove \
    &&  pecl install xdebug \
    &&  echo "zend_extension=$(find /usr/lib/php/ -name xdebug.so)" > /etc/php/8.0/apache2/conf.d/xdebug.ini \
    &&  echo "xdebug.remote_enable=on" >> /etc/php/8.0/apache2/conf.d/xdebug.ini \
    &&  echo "xdebug.remote_autostart=off" >> /etc/php/8.0/apache2/conf.d/xdebug.ini \
    &&  wget -q -O /usr/local/bin/composer https://getcomposer.org/download/latest-stable/composer.phar \
    &&  chmod +x /usr/local/bin/composer

VOLUME ["/var/log/ampache", "/var/www/html"]
EXPOSE 80

COPY docker/data/bin/apache2.sh docker/data/bin/run.sh docker/data/bin/composer.sh /usr/local/bin/
COPY docker/data/sites-enabled/001-ampache.conf /etc/apache2/sites-available/
COPY docker/data/conf.d/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN  chmod +x /usr/local/bin/*.sh

CMD ["run.sh"]
