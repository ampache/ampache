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

PATH=$PATH:/bin:/usr/bin:/usr/local/bin

# gettext package test
if [ ! -x `which xgettext` ]
then
	echo "xgettext was not found. please install gettext packages."
	exit 0;
fi

SCRIPTS=$0
MAILADDR="translations at ampache.org"
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
	echo "usage: $SCRIPTS [--help|--get|--init|--merge|--format|--all]"
	exit
fi

case $1 in
	"--all"|"-a"|"all")
		xgettext --from-code=UTF-8 --msgid-bugs-address="$MAILADDR" -L php -o $POTNAME `find ../../ -name \*.php -type f` `find ../../ -name \*.inc -type f`
		OLANG=`ls ../ | grep -v base`
		echo "add database words add to pot file..."
		cat translation-words.txt >> messages.pot
		for i in $OLANG
		do
			echo "$i PO file merging..."
			msgmerge --update ../$i/LC_MESSAGES/messages.po messages.pot
			echo "$i MO file creating..."
			msgfmt -v -c ../$i/LC_MESSAGES/messages.po -o ../$i/LC_MESSAGES/messages.mo
			rm -f ../$i/LC_MESSAGES/messages.po~
			obs=`cat ../$i/LC_MESSAGES/messages.po | grep '^#~' | wc -l`
			echo "Obsolete: $obs"
		done
		;;
	"--get"|"-g"|"get")
		xgettext --from-code=UTF-8 --msgid-bugs-address="$MAILADDR" -L php -o $POTNAME `find ../../ -name \*.php -type f` `find ../../ -name \*.inc -type f`;
		if [ $? = 0 ]; then
			echo "pot file creation was done.";
		else
			echo "pot file creation wasn't done.";
		fi
		echo "add database words to pot file..."
		cat translation-words.txt >> messages.pot
		;;
	"--init"|"-i"|"init")
		msginit -l $LANG -i $POTNAME -o $PODIR/$PONAME;
		;;
	"--format"|"-f"|"format")
		msgfmt -v --check $PODIR/$PONAME -o $PODIR/$MONAME;
		;;
	"--merge"|"-m"|"merge")
		msgmerge --update $PODIR/$PONAME $POTNAME;
		rm -f $PODIR/messages.po~
		;;
	"--help"|"-h"|"help"|"*")
		echo "usage: $SCRIPTS [--help|--get|--init|--merge|--format|--all]";
		echo "";
		echo "Please read for translation: http://ampache.org/wiki/dev:translation";
		;;
esac
