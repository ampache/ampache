#!/bin/sh

# create the htaccess files
if [ ! -f ./public/channel/.htaccess ]; then
  cp ./public/channel/.htaccess.dist ./public/channel/.htaccess
fi

if [ ! -f ./public/play/.htaccess ]; then
  cp ./public/play/.htaccess.dist ./public/play/.htaccess

fi
if [ ! -f ./public/rest/.htaccess ]; then
  cp ./public/rest/.htaccess.dist ./public/rest/.htaccess
fi

# create the docker volume folders
if [ ! -d ./docker/log ]; then
  mkdir ./docker/log
fi
if [ ! -d ./docker/media ]; then
  mkdir ./docker/media
fi

# reset perms
chown $UID:33 ./docker/log
chmod 775 ./docker/log

chown $UID:33 ./docker/media
chmod 775 ./docker/media

chown $UID:33 ./config
chmod -R 775 ./config

docker-compose up -d
