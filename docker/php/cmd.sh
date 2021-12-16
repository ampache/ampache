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
    --dbuser root \
    --dbpassword secret \
    --ampachedbuser ampache \
    --ampachedbpassword ampache\
    --webpath "/public"
# Point to redis container
sed -i -e 's/.*memory_cache =.*/memory_cache = "true"/g' config/ampache.cfg.php
sed -i -e 's/.*redis_host =.*/redis_host = "redis"/g' config/ampache.cfg.php
sed -i -e 's/.*redis_port =.*/redis_port = 6379/g' config/ampache.cfg.php
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

# Enable logging ampache debug output
# Still have to modify config and set debug = "true"
sed -i -e 's|log_path = "/var/log/.+"|log_path = "/var/log/"|g' config/ampache.cfg.php
sed -i -e 's|log_filename = "%name.%Y%m%d.log"|log_filename = "ampache.log"|g' config/ampache.cfg.php

touch /var/log/ampache.log
chmod a+rw /var/log/ampache.log
php-fpm &> /var/log/php.log &

# Output the debug logs and the php-fpm process
tail --lines=1 --follow --retry /var/log/php.log /var/log/ampache.log

# Kill the php-fpm process
kill %1
