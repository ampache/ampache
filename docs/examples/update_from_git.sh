#!/bin/sh

### This is an example file that i use to keep my servers updated.
### Comment and Uncomment the lines you want to use

### Set your ampache root folder
AMPACHEDIR="/var/www/ampache"

BRANCH='develop'
#BRANCH='patch6'
#BRANCH='release6'

### What's the folder being updated
echo $AMPACHEDIR

### Do you dev? if you're editing files you can check out the original before updating
#cd $AMPACHEDIR && git checkout composer.json public src bin config tests locale docs docker

### cd to the folder, pull your branch and check for database updates
cd $AMPACHEDIR && git pull && git checkout -f $BRANCH && git reset --hard origin/$BRANCH && git pull && php bin/cli admin:updateDatabase -e

### Don't use php8.2? you need the old composer
#cp -f $AMPACHEDIR/composer_old.json $AMPACHEDIR/composer.json

### You don't always need to do this but some people might want to keep composer packages updated here
#composer update

