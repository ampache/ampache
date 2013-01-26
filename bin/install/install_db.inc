<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
/**
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright 2001 - 2013 Ampache.org
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */

if(php_sapi_name() != 'cli') {
        exit(1);
}

define('NO_SESSION', 1);
define('CLI', 1);

$path = dirname(__FILE__);
$prefix = realpath($path . '/../../');
require_once $prefix . '/lib/init-tiny.php';
require_once $prefix . '/lib/install.lib.php';

$options = getopt(
    'h:d:f:p:P:u:U:w:',
    array(    'database-user:',
        'database-password:',
        'database-host:',
        'database-name:',
        'ampache-database-user:',
        'ampache-database-password:',
        'webpath:',
        'force'
    )
);

$force = ($options['f'] || $options['force']);

$db_user = $options['U'] ?: $options['database-user'];
$db_pass = $options['P'] ?: $options['database-password'];
$db_host = $options['h'] ?: $options['database-host'];
$db_name = $options['d'] ?: $options['database-name'];
$new_db_user = $options['u'] ?: $options['ampache-database-user'];
$new_db_pass = $options['p'] ?: $options['ampache-database-password'];
$webpath = $options['w'] ?: $options ['webpath'];

// Make sure we have all the required information
if (!$db_user || !$db_pass || !$db_host || !$db_name || !$new_db_user || !$new_db_pass) {
    usage();
}

// Now let's make sure its not already installed
if (!install_check_status($configfile)) { 
    echo "\n", T_('Existing Ampache installation found.'), "\n";
    if ($force) {
        echo T_('Force specified, proceeding anyway.'), "\n";
    }
    else {
        echo T_('Exiting.'), "\n"; 
        exit(1);
    }
} 

// Install the database
if (!install_insert_db($db_user, $db_pass, $db_host, $db_name, $new_db_user, $new_db_pass)) { 
    echo T_('Database creation failed'), "\n"; 
    echo Error::get('general'), "\n\n"; 
    exit(1); 
} 

// Write the config file
if (!install_create_config($webpath, $new_db_user, $new_db_pass, $db_host, $db_name)) { 
    echo T_('Config file creation failed'), "\n"; 
    echo Error::get('general') . "\n\n"; 
    exit(1); 
} 

/**
 * usage
 * This just prints out the required params for this script
 **/
function usage() {
    echo "Usage: install_db.inc [options]";
    echo "\n\t-U, --database-user\t\t\t";
    echo 'MySQL Admin User';
    echo "\n\t-P, --database-password\t\t\t";
    echo 'MySQL Admin Password';
    echo "\n\t-h, --database-host\t\t\t";
    echo 'MySQL Hostname';
    echo "\n\t-d, --database-name\t\t\t"; 
    echo "MySQL Database Name"; 
    echo "\n\t-u, --ampache-database-user\t";
    echo 'MySQL Application Username';
    echo "\n\t-p, --ampache-database-pass\t"; 
    echo 'MySQL Application Password'; 
    echo "\n\t-w, --webpath\t\t\t\t"; 
    echo 'Web path';
    echo "\n\t-f, --force\t\t\t\t";
    echo 'Force installation', "\n";

    exit(1);
}

?>
