#!/bin/bash
###############################################
# Shell script to backup contents of the Ampache
#  www directory, settings file, and dump the
#  contents of the Ampache database to compressed
#  files suitable for backups offsite or to
#  migrate the database to a new server
#
# Information:
#  This script takes no arguments.  You will need
#  to modify the 3 lines below to match your system.

###############################################
# CHANGE THESE OPTIONS TO MATCH YOUR SYSTEM ! #
###############################################

cfgfile=/etc/ampache/ampache.cfg.php            # Ampache config file
ampachedir=/usr/share/ampache/www               # the directory Ampache is installed in
backupdir=~/ampache_backup                      # the directory to write the backup to

###############################################
#         END OF CONFIGURABLE OPTIONS         #
###############################################

ampacheDBserver=`grep "^database_hostname" $cfgfile | cut --delimiter="=" -f2 | cut --delimiter="\"" -f2`
ampacheDB=`grep "database_name" $cfgfile | cut --delimiter="=" -f2 | cut --delimiter="\"" -f2`
ampacheDBuser=`grep "database_username" $cfgfile | cut --delimiter="=" -f2 | cut --delimiter="\"" -f2`
ampacheDBpassword=`grep database_password $cfgfile | cut --delimiter="=" -f2 | cut --delimiter="\"" -f2`

mysqlopt="--host=$ampacheDBserver --user=$ampacheDBuser --password=$ampacheDBpassword"

timestamp=`date +%Y-%m-%d`
 
dbdump="$backupdir/ampache-$timestamp.sql.gz"
filedump="$backupdir/ampache-$timestamp.files.tar.gz"
cfgdump="$backupdir/ampache-$timestamp.cfg.tar.gz"
 
date
echo "Ampache backup."
echo "Database:  $ampacheDB"
echo "Login:     $ampacheDBuser / $ampacheDBpassword"
echo "Directory: $ampachedir"
echo "Config:    $cfgfile"
echo "Backup to: $backupdir"
echo
echo "creating database dump..."
mysqldump --default-character-set=utf8 $mysqlopt "$ampacheDB" | gzip > "$dbdump" || exit $?
 
echo "creating file archive of $ampachedir ..."
cd "$ampachedir"
tar --exclude .svn -zcf "$filedump" . || exit $?

echo "backing up $cfgfile ..."
tar --exclude .svn -zcf "$cfgdump" $cfgfile || exit $?

echo "Done!"
echo "Backup files:"
ls -l $dbdump
ls -l $filedump
ls -l $cfgdump
echo "******************************************"
echo
