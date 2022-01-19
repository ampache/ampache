#!/bin/bash

sudo su - www-data
cd /var/www/html
composer install --prefer-dist --no-interaction
