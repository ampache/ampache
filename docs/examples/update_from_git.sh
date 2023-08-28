#!/bin/sh -e

### This is an example file that i use to keep my servers updated.
### Comment and Uncomment the lines you want to use

### Set your ampache root folder
AMPACHEDIR="/var/www/ampache"

BRANCH='develop'
#BRANCH='patch6'
#BRANCH='release6'

### What's the folder being updated
echo $AMPACHEDIR

### cd to the folder
cd $AMPACHEDIR

### Do you dev? if you're editing files you can check out the original before updating
git checkout composer.json public src bin config tests locale docs docker

### Update your local branch
git pull
git checkout -f $BRANCH
git reset --hard origin/$BRANCH
git pull

### Check for database updates
php bin/cli admin:updateDatabase -e

### Don't use php8.2? you need the old composer
#cp -f $AMPACHEDIR/composer_old.json $AMPACHEDIR/composer.json

### You don't always need to do this but some people might want to keep composer packages updated here
#composer update
