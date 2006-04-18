<?php
/*

 Copyright (c) 2001 - 2006 ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License
 as published by the Free Software Foundation; either version 2
 of the License, or (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

*/

// Set the Error level manualy... I'm to lazy to fix notices
error_reporting(E_ALL ^ E_NOTICE);

require_once('lib/general.lib.php');
require_once('lib/ui.lib.php');
require_once('modules/horde/Browser.php');
require_once('lib/install.php');
require_once('modules/lib.php');
require_once('lib/debug.php');
require_once('lib/class/user.class.php');
require_once('lib/class/error.class.php');

require_once('modules/vauth/dbh.lib.php');
require_once('modules/vauth/init.php');

if ($_SERVER['HTTPS'] == 'on') { $http_type = "https://"; }
else { $http_type = "http://"; }



$prefix = dirname(__FILE__); 
$configfile = "$prefix/config/ampache.cfg.php";

$conf_array = array('prefix' => $prefix,'font_size' => '12', 'bg_color1' => '#c0c0c0', 'font' => 'Verdana', 'error_color' => 'red');
$conf_array['base_color1'] = "#a0a0a0";
$conf_array['bg_color2']   = "#000000";
conf($conf_array);

/* First things first we must be sure that they actually still need to 
   install ampache 
*/
if (!install_check_status($configfile)) { 
	access_denied();
}

/* Clean up incomming variables */
$action = scrub_in($_REQUEST['action']);
$web_path = scrub_in($_REQUEST['web_path']);
$username = scrub_in($_REQUEST['local_username']);
$password = scrub_in($_REQUEST['local_pass']);
$hostname = scrub_in($_REQUEST['local_host']);
$database = scrub_in($_REQUEST['local_db']);
if ($_SERVER['HTTPS'] == 'on') { $http_type = "https://"; }
else { $http_type = "http://"; }
$php_self = $http_type . $_SERVER['HTTP_HOST'] . "/" . preg_replace("/^\/(.+\.php)\/?.*/","$1",$_SERVER['PHP_SELF']);
$error	  = new Error();

/* Catch the Current Action */
switch ($action) { 

	case 'create_db':
		if (!install_insert_db($username,$password,$hostname,$database)) { 
			require_once('templates/show_install.inc.php');
			break;
		}
		
		header ("Location: " . $php_self . "?action=show_create_config&local_db=$database&local_host=$hostname");
		
		break;
	case 'create_config':
		$created_config = install_create_config($web_path,$username,$password,$hostname,$database);

		require_once('templates/show_install_config.inc');
		break;
	case 'show_create_config':
	
                /* Attempt to Guess the Web_path */
		$web_path = dirname($_SERVER['PHP_SELF']);
		$web_path = rtrim($web_path,"\/");
	
		require_once('templates/show_install_config.inc');
		break;
	case 'create_account':
		if (!install_create_account($username,$password)) { 
			require_once('templates/show_install_account.inc.php');
			break;
		}
		$results = read_config($configfile, 0, 0);

		$results['mysql_hostname'] = $results['local_host'];
		$results['mysql_username'] = $results['local_username'];
		$results['mysql_password'] = $results['local_pass'];
		$results['mysql_db']	   = $results['local_db'];
			
		if ($_SERVER['HTTPS'] == 'on') { $http_type = "https://"; }
		else { $http_type = "http://"; }

		vauth_conf($results);
		/* Setup Preferences */
		$temp_user = new User($username);
		$temp_user->fix_preferences();
		$temp_user = new User(-1);
		$temp_user->username = '-1';
		$temp_user->fix_preferences();

	
		$web_path = $http_type . $_SERVER['HTTP_HOST'] . $results['web_path'];

		header ("Location: " . $web_path . "/login.php");
	
	case 'show_create_account':
	
		$results = read_config($configfile, 0, 0);
	
		/* Make sure we've got a valid config file */
		if (!read_config_file($configfile) OR !check_config_values($results)) { 
			require_once('templates/show_install_config.inc'); 
			break;
		}
	
		require_once('templates/show_install_account.inc.php');
		break;
	default:
		require_once('templates/show_install.inc');
		break;

} // end action switch


?>
