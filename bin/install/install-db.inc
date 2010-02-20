<?php
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

array_shift($_SERVER['argv']); 

if (count($_SERVER['argv']) != 16) { 
	usage();
} 








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
