#!/bin/bash
#
# vim:set softtabstop=4 shiftwidth=4 expandtab:
#
# Copyright 2001 - 2015 Ampache.org
#
# This program is free software; you can redistribute it and/or
# modify it under the terms of the GNU General Public License v2
# as published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
#

PATH=$PATH:/bin:/usr/bin:/usr/local/bin

# gettext package test
if ! which xgettext &>/dev/null ; then
    echo "Xgettext was not found. Do you need to install gettext?"
    exit 1;
fi

[[ $OLANG ]] || OLANG=$(echo $LANG | sed 's/\..*//;')
potfile='messages.pot'
tdstxt='translatable-database-strings.txt'
ampconf='../../config/ampache.cfg.php'

usage() {
    echo ""
    echo -e "\033[32m usage: $0 [-h|--help][-g|--get][-gu|--getutds][-i|--init][-m|--merge][-f|--format][-a|--all][-au|--allutds]\033[0m"
    echo ""
    echo -e "[-g|--get]\t\t Creates the messages.pot file from translation strings within the source code."
    echo -e "[-gu|--getutds]\t\t Generates the Pot file from translation strings within the source code\n\t\t\t and creates or updates the 'translatable-database-strings.txt' from the database-preference-table strings.\n\t\t\t Ampache needs to be fully setup for this to work."
    echo -e "[-i|--init]\t\t Creates a new language catalog and its directory structure."
    echo -e "[-m|--merge]\t\t Merges the messages.pot into the language catalogs and shows obsolet translations."
    echo -e "[-ma|--mergeall]\t Same as -m but for all translations."
    echo -e "[-f|--format]\t\t Compiles the .mo file for its related .po file."
    echo -e "[-fa|--formatall]\t Same as -f but for all translations."
    echo -e "[-a|--all]\t\t Does all except --init and --utds."
    echo -e "[-au|--allutds]\t\t Does all except --init"
    echo -e "[-h|--help]\t\t Shows this help screen."
    echo ""
    echo -e "\033[32m If you encounter any bugs, please report them on Transifex (https://www.transifex.com/projects/p/ampache/)\033[0m"
    echo -e "\033[32m See also: https://github.com/ampache/ampache/blob/master/locale/base/TRANSLATIONS\033[0m"
    echo ""
    exit 1
}

generate_pot() {
    echo "Generating/updating pot-file"
    xgettext    --from-code=UTF-8 \
                --add-comment=HINT: \
                --msgid-bugs-address="https://www.transifex.com/projects/p/ampache/" \
                -L php \
                --keyword=gettext_noop --keyword=T_ --keyword=T_gettext --keyword=T_ngettext --keyword=ngettext \
                -o $potfile \
                $(find ../../ -type f -name \*.php -o -name \*.inc | sort)
    if [[ $? -eq 0 ]]; then
        echo -e "\033[32m Pot file creation succeeded. Adding 'translatable-database-strings.txt\033[0m"
        cat $tdstxt >> $potfile
        echo -e "\n\033[32m Done, you are able now to use the messages.pot for further translation tasks.\033[0m"
    else
        echo -e "\033[31m Error\033[0m: Pot file creation has failed!"
    fi
}

generate_pot_utds() {
    echo ""
    echo "Generating/updating pot-file"
    echo ""
    xgettext    --from-code=UTF-8 \
                --add-comment=HINT: \
                --msgid-bugs-address="https://www.transifex.com/projects/p/ampache/" \
                -L php \
                --keyword=gettext_noop --keyword=T_ --keyword=T_gettext --keyword=T_ngettext --keyword=ngettext \
                -o $potfile \
                $(find ../../ -type f -name \*.php -o -name \*.inc | sort)
    if [[ $? -eq 0 ]]; then

        ampconf='../../config/ampache.cfg.php'

        echo -e "\033[32m Pot creation/update successful\033[0m\n"
        echo -e "Reading database login information from Amapche config file\n"

        dbhost=$(grep -oP "(?<=database_hostname = \")[^\"\n]+" $ampconf)
        if [ ! $dbhost ]; then
            echo -e "\n\033[31m Error\033[0m: No or false database host setting detected!"
            read -r -p "Type in a host or simply press enter to use localhost instead: " dbhost
                if [ ! $dbhost ]; then
                    dbhost=localhost
                else
                    continue
                fi
        fi
        echo "Temporary saved '$dbhost' as your database host"

        dbport=$(grep -oP "(?<=database_port = \")[^\"\n]+" $ampconf)
        if [ ! $dbport ]; then
            echo ""
            echo -e "\033[31m Error\033[0m: No or false database_port setting detected!"
            read -r -p "Type in a port or simply press enter to use default port 3306 instead: " dbport
                if [ ! $dbport ]; then
                    dbport=3306
                fi
        fi
        echo "Temporary saved '$dbport' as your database port"

        dbname=$(grep -oP "(?<=database_name = \")[^\"\n]+" $ampconf)
        if [ ! $dbname ]; then
            echo ""
            echo -e "\033[31m Error\033[0m: No datatabase name detected, please check your 'database_name' setting"
            read -r -p "or type in the right database name here for temporary use: " dbname
                if [ ! $dbname ]; then
                    echo ""
                    echo -e "\033[31m Error\033[0m: You didn't type in a database name, Sorry but I have to exit :("
                    exit
                fi
        fi
        echo "Temporary saved '$dbname' as your database name"

        dbuser=$(grep -oP "(?<=database_username = \")[^\"\n]+" $ampconf)
        if [ ! $dbuser ]; then
            echo -e "\n\033[31m Error\033[0m: You need to set a valid database user in you Ampache config file"
            read -r -p "Or type it in here for temporary use: " dbuser
                if [ ! $dbuser ]; then
                    echo -e "\n\033[31m Error\033[0m: You didn't type in a database user! Sorry but I have to exit :("
                    exit
                fi
        fi
        echo "Temporary saved '$dbuser' as your database user"

            dbpass=$(grep -oP "(?<=database_password = \")[^\"\n]+" $ampconf)
        if [ ! $dbpass ]; then
            echo -e "\n\033[32m Info\033[0m: You haven't set a database password in your Amapche config."
            echo "If this is OK, simply press enter to continue."
            read -r -p "Otherwise type one in for temporary use: " dbpass
            if [ ! $dbpass ]; then
                echo "Okay, you've selected to use no password, proceeding."
            else
                echo "Temporary saved '$dbpass' as your database password"
            fi
        else
            echo "Temporary saved '$dbpass' as your database password"
        fi

        echo ""
        echo "Deleting old translatable-database-strings.txt"
        echo ""
        rm $tdstxt

        echo -e "Creating new 'translatable-database-strings.txt' from database\n"

        echo -e " #######################################################################\n\n"\
                "# This file lists all description strings from your Ampache-database -> preference-table.\n"\
                "# Please do not delete or modify the content by yourself. It will be automatically (re)generated\n"\
                "# if you run './gather-messages.sh [-gu|--getutds].\n"\
                "# Last Update: $(date "+%d.%m.%Y %H:%M:%S %Z")"\
                "\n\n"\
                "#######################################################################" >> $tdstxt

        mysql -N --database=$dbname --host=$dbhost --user=$dbuser --password=$dbpass -se "SELECT id FROM preference" | 
        while read dbprefid; do
            dbprefdesc=$(mysql -N --database=$dbname --host=$dbhost --user=$dbuser --password=$dbpass -se "SELECT description FROM preference where id=$dbprefid")
            dbprefdescchk=$(grep "\"$dbprefdesc\"" $potfile)
            if [ ! "$dbprefdescchk" ]; then
                echo -e "\n#: Database preference table id $dbprefid" >> $tdstxt
                echo -e "msgid \"$dbprefdesc\"" >> $tdstxt
                echo -e "msgstr \"\"" >> $tdstxt
            else
                echo -e "\n#: Database preference table id $dbprefid" >> $tdstxt
                echo -e "# is already in the source code\n# but to avoid confusion, it's added and commented" >> $tdstxt
                echo -e "# msgid \"$dbprefdesc\"" >> $tdstxt
                echo -e "# msgstr \"\"" >> $tdstxt
            fi
        done
        echo -e "\033[32m Pot file creation succeeded. Adding 'translatable-database-strings.txt\033[0m"
        cat $tdstxt >> $potfile
        echo -e "\n\033[32m Done, you are able now to use the messages.pot for further translation tasks.\033[0m"
    else
        echo -e "\033[31m Error\033[0m: Pot file creation has failed!"
    fi
}

do_msgmerge() {
    source=$potfile
    target="../$1/LC_MESSAGES/messages.po"
    echo "Merging $source into $target"
    msgmerge --update --backup=off $target $source
    echo "Obsolete messages in $target: " $(grep '^#~' $target | wc -l)
}

do_msgfmt() {
    source="../$1/LC_MESSAGES/messages.po"
    target="../$1/LC_MESSAGES/messages.mo"
    echo "Creating $target from $source"
    msgfmt --verbose --check $source -o $target
}

if [[ $# -eq 0 ]]; then
    usage
fi

case $1 in
    '-a'|'--all')
        generate_pot
        for i in $(ls ../ | grep -v base); do
            do_msgmerge $i
            do_msgfmt $i
        done
    ;;
    '-au'|'--allutds')
        generate_pot_utds
        for i in $(ls ../ | grep -v base); do
            do_msgmerge $i
            do_msgfmt $i
        done
    ;;
    '-g'|'--get')
        generate_pot
    ;;
    '-gu'|'--getutds')
        generate_pot_utds
    ;;
    '-i'|'--init'|'init')
        outdir="../$OLANG/LC_MESSAGES"
        [[ -d $outdir ]] || mkdir -p $outdir
        msginit -l $LANG -i $potfile -o $outdir/messages.po
    ;;
    '-f'|'--format'|'format')
        do_msgfmt $OLANG
    ;;
    '-fa'|'--formatall')
        for i in $(ls ../ | grep -v base); do
            do_msgfmt $i
        done
    ;;
    '-m'|'--merge'|'merge')
        do_msgmerge $OLANG
    ;;
    '-ma'|'--mergeall')
        for i in $(ls ../ | grep -v base); do
            do_msgmerge $i
        done
    ;;
    '-h'|'--help'|'help'|'*')
        usage
    ;;
esac
