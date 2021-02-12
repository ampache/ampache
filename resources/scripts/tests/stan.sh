#!/bin/bash

if [ -e "vendor/bin/phpstan" ]
then
    ./vendor/bin/phpstan analyse
else
    echo -e "\e[1;31mphpstan not found: Please run composer install --dev\e[00m";
    exit 1
fi
