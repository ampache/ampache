<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/*

 Copyright Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

define('NO_SESSION','1');
$path = dirname(__FILE__);
$prefix = realpath($path . '/../../');
require_once $prefix . '/lib/init.php';
require_once 'lib/install.php';
require_once 'lib/debug.lib.php';

Config::set('prefix',$prefix,'1');
$configfile = "$prefix/config/ampache.cfg.php";


array_shift($_SERVER['argv']); 

if (count($_SERVER['argv']) != 18) { 
	usage();
	exit; 
} 

$input = array(); 

// Build up the key'd array
while (count($_SERVER['argv']) > 0) { 

	// Make sure that we have the expected option pattern
	if (substr($_SERVER['argv']['0'],0,1) != '-') { 
		echo "\nINVALID OPTIONS PASSED\n"; 
		usage(); 
		exit; 
	} 

	// Get the true name of the option minus the - 	
	$var = substr($_SERVER['argv']['0'],1,strlen($_SERVER['argv']['0'])-1); 

	// Done with it, pop it off!
	array_shift($_SERVER['argv']); 

	// Get it's paried value
	$input[$var] = array_shift($_SERVER['argv']); 

} // while reading options

// Now let's make sure its not already installed
if (!install_check_status($configfile)) { 
	echo "\nExisting Ampache Installation Found... exiting\n\n"; 
//	exit; 
} 

// Install the database
if (!install_insert_db($input['dbadmuser'],$input['dbadmpass'],$input['dbhost'],$input['dbname'],$input['dbuser'],$input['dbpass'])) { 
	echo "\nUnable to create database\n"; 
	echo Error::get('general') . "\n\n"; 
	exit; 
} 

// Write the config file
if (!install_create_config($input['webpath'],$input['dbuser'],$input['dbpass'],$input['dbhost'],$input['dbname'])) { 
	echo "\nUnable to create config file\n"; 
	echo Error::get('general') . "\n\n"; 
	exit; 
} 

// Create the initial user
if (!install_create_account($input['user'],$input['pass'],$input['pass'])) { 
	echo "\nUnable to create initial user\n"; 
	echo Error::get('general') . "\n\n"; 
	exit; 
} // initial user

// Return no errors if we've made it this far
echo 0; 
exit; 

/**
 * usage
 * This just prints out the required params for this script
 **/
function usage() {
	
	echo "- Install Database -"; 
	echo "\n";
	echo "Usage: install_db.inc [options]";
	echo "\n\t-dbadmuser\t";
	echo 'MySQL Admin User';
	echo "\n\t-dbadmpass\t";
	echo 'MySQL Admin Password';
	echo "\n\t-dbhost\t\t";
	echo 'MySQL Hostname';
	echo "\n\t-dbname\t\t"; 
	echo "MySQL Database Name"; 
	echo "\n\t-dbuser\t\t";
	echo 'MySQL Application Username';
	echo "\n\t-dbpass\t\t"; 
	echo 'MySQL Application Password'; 
	echo "\n\t-user\t\t";
	echo 'Username';
	echo "\n\t-pass\t\t";
	echo 'Password'; 
	echo "\n\t-webpath\t"; 
	echo 'Webpath'; 
	echo "\n------------------------------------\n";
}

?>
