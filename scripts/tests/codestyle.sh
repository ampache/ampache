#!/bin/bash

if [ -e "php-cs-fixer.phar" ]
then
    PHPCSFIXER="php php-cs-fixer.phar"
elif [ -e "php-cs-fixer" ]
then
    PHPCSFIXER="php php-cs-fixer"
else
    echo -e "\e[1;31mPlease install or download latest stable php-cs-fixer\e[00m";
    echo -e "\e[1;31mhttp://cs.sensiolabs.org/\e[00m";
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
    EXIT=1
fi

#echo -e "\e[1;34mChecking optionnal formatting/coding standards\e[00m"
#$PHPCSFIXER $PHPCSFIXERARGS$FIXERS2 --dry-run .
#rc=$?
#if [[ $rc == 0 ]]
#then
#    echo -e "\e[1;32mOptionnal formatting is OK\e[00m"
#else
#    echo -e "\e[1;33mThere are errors in the formatting (or false positive)\e[00m"
#    echo -e "\e[1;33m$PHPCSFIXER $PHPCSFIXERARGS$FIXERS2 .\e[00m"
#fi

exit $EXIT
