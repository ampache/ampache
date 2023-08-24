#!/bin/sh

# create the htaccess files
if [ ! -f ../public/play/.htaccess ]; then
  cp ../public/play/.htaccess.dist ../public/play/.htaccess
fi
if [ ! -f ../public/rest/.htaccess ]; then
  cp ../public/rest/.htaccess.dist ../public/rest/.htaccess
fi

# create the docker volume folders
if [ ! -d ./log ]; then
  mkdir ./log
fi
if [ ! -d ./media ]; then
  mkdir ./media
fi

# reset perms
chown $UID:33 ./log
chmod 775 ./log

chown $UID:33 ./media
chmod 775 ./media

chown $UID:33 ../composer.json
chmod 775 ../composer.json
chown -R $UID:33 ../config
chmod -R 775 ../config
chown -R $UID:33 ../vendor/
chmod -R 775 ../vendor/
chown -R $UID:33 ../public/
chmod -R 775 ../public/

# remove the lock
#if [ -f ../composer.lock ]; then
#  rm ../composer.lock
#fi

chown $UID:33 ./log
chmod 775 ./log
chown $UID:33 ./media
chmod 775 ./media

chown $UID:33 ../
chmod 775 ../
