#!/bin/sh -e

### This is an example file that i use to keep my servers updated.
### Comment and Uncomment the lines you want to use

### Set your ampache root folder
AMPACHEDIR="/var/www/ampache"

BRANCH='develop'
#BRANCH='patch7'
#BRANCH='release7'

### What's the folder being updated
echo $AMPACHEDIR

### cd to the folder
cd $AMPACHEDIR

### Do you dev? if you're editing files you can check out the original before updating
git checkout composer.json composer.lock public src bin config tests locale docs docker

### Update your local branch
git pull
git checkout -f $BRANCH
git reset --hard origin/$BRANCH
git pull

### Check for database updates
php bin/cli admin:updateDatabase -e

### Check for config file updates
php bin/cli admin:updateConfigFile -e

### Check for plugin updates
php bin/cli admin:updatePlugins -e

### Clean up your garbage data
php bin/cli run:updateCatalog -t

### Run a public site? you can forcibly set all preferences to admin and stop users changing things
#php bin/cli admin:updatePreferenceAccessLevel -e -l admin

### You don't always need to do this but some people might want to keep composer packages updated here
### You might want the dev packages as well so you can remove '--no-dev' to install those
#composer install --no-dev --prefer-source --no-interaction

### NPM is now required to handle all the javascript packages
npm install
npm run build
