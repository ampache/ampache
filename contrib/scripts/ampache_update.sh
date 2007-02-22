#!/bin/sh

################################################
#                                              #
#	Update ampache through svn	       #
#                                              #
################################################

################################################
#                                              #
#	Base Path of ampache		       #
#   Change this to you're ampache base_path    #
#                                              #
################################################

base_path=/srv/www/ampache

################################################
#                                              #
# No need to change anything beneath this line #
#                                              #
################################################

path=`pwd`

echo "Updating Ampache Core Application"
cd $base_path
svn up
svnversion -n . > version.php

for theme in `ls -d themes/*`
    do
    cd $theme
    name=`grep name theme.cfg.php | grep = | tr '=' ' ' |awk '{print $2}'`
    echo "Updating Ampache Theme $name"
    svn up
    cd ../../
    
    done


cd $path
