#!/bin/bash

if [ -e "vendor/bin/php-cs-fixer" ]
then
    PHPCSFIXER="vendor/bin/php-cs-fixer"
else
    echo -e "\e[1;31mphp-cs-fixer not found: Please run composer install --dev\e[00m";
    exit 1
fi

PHPCSFIXERARGS="fix -v"

EXIT=0

echo -e "\e[1;34mChecking mandatory formatting/coding standards\e[00m"
$PHPCSFIXER $PHPCSFIXERARGS --dry-run --diff
rc=$?
if [[ $rc == 0 ]]
then
    echo -e "\e[1;32mFormatting is OK\e[00m"
else
    echo -e "\e[1;31mPlease check code Formatting\e[00m"
    echo -e "\e[1;31m$PHPCSFIXER $PHPCSFIXERARGS$FIXERS1 .\e[00m"
    exit 1
fi

if [ -e "vendor/bin/phpcbf" ]
then
    PHPCS="vendor/bin/phpcs"
else
    echo -e "\e[1;31mphpcs not found: Please run composer install --dev\e[00m";
    exit 1
fi

PHPCSARGS="--standard=.phpcs.xml src public"

$PHPCS $PHPCSARGS
rc=$?
if [[ $rc == 0 ]]
then
    echo -e "\e[1;32mOptional formatting is OK\e[00m"
else
    echo -e "\e[1;33mThere are errors in the formatting (or false positive)\e[00m"
    echo -e "\e[1;33m$PHPCS $PHPCSARGS .\e[00m"
    exit 1
fi
