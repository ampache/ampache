#!/bin/sh
#
# Copyright (c) Ampache.org
# All rights reserved.
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

SCRIPTS=$0
MAILADDR="translation@ampache.org"
OLANG=`echo $LANG | sed 's/\..*//;'`
POTNAME="messages.pot"
PODIR="../$OLANG/LC_MESSAGES"
PONAME="messages.po"
MONAME="messages.mo"

if [ ! -d $PODIR ]
then
       mkdir -p $PODIR
       echo "$PODIR has created."
fi

if [ $# = 0 ]; then
    echo "usage: $SCRIPTS [--help|--get|--init|--merge]"
    exit
fi

case $1 in
        "--get"|"-g"|"get")
                xgettext --from-code=UTF-8 --msgid-bugs-address=$MAILADDR -L php -o $POTNAME `find ../../ -name \*.php -type f` `find ../../ -name \*.inc -type f`;
                if [ $? = 0 ]; then
                        echo "pot file creation was done.";
                else
                        echo "pot file creation wasn't done.";
                fi
                ;;
        "--init"|"-i"|"init")
                msginit -l $LANG -i $POTNAME -o $PODIR/$PONAME;
                ;;
        "--format"|"-f"|"format")
                msgfmt -v --check $PODIR/$PONAME -o $PODIR/$MONAME;
                ;;
        "--merge"|"-m"|"merge")
                msgmerge --update $PODIR/$PONAME $POTNAME;
                ;;
        "--help"|"-h"|"help"|"*")
                echo "usage: $SCRIPTS [--help|--get|--init|--merge]";
                ;;
esac
