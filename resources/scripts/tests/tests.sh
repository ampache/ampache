#!/bin/bash

if [ -e "vendor/bin/phpunit" ]
then
    ./vendor/bin/phpunit tests
else
    echo -e "\e[1;31mphpunit not found: Please run composer install --dev\e[00m";
    exit 1
fi
