#!/bin/bash
set -xeuo pipefail
# Execute commands without XDEBUG
rm -f /usr/local/etc/php/conf.d/xdebug.ini

composer install

# Give access to all dirs
find -type d -exec chmod +rx '{}' \;
# Give read access to all files
chmod -R +r .
# Ensure /media can be written to
chmod 777 /media/

# Install ampache (install db and write config)
bin/installer install \
    --dbhost db \
    --dbname ampache \
    --dbuser ampache \
    --dbpassword ampache \
    --webpath "/public"
chmod a+rw config/ampache.cfg.php

# Add admin user if necessary
bin/cli admin:addUser \
    --email ampache@test.test \
    --password ampache \
    --level 100 \
    ampache || true

# https://xdebug.org/docs/step_debug#configure
# https://stackoverflow.com/questions/48026670/configure-xdebug-in-php-fpm-docker-container#48243590
# configure xdebug to connect to port 9003 on the host by default

if [[ "${XDEBUG_SESSION:-}" = "1" ]] ; then
    echo "Enabling XDEBUG remote session"
    echo "
    xdebug.mode=debug
    xdebug.remote_enable=true
    xdebug.client_port=${XDEBUG_PORT:-9003}
    xdebug.client_host=${XDEBUG_HOST:-host.docker.internal}
    " > /usr/local/etc/php/conf.d/xdebug.ini
fi

exec php-fpm
