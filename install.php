<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation

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

require_once 'lib/general.lib.php';
require_once 'lib/class/config.class.php';
require_once 'lib/class/error.class.php'; 
require_once 'lib/class/vauth.class.php';
require_once 'lib/class/database_object.abstract.php';
require_once 'lib/class/preference.class.php'; 
require_once 'lib/class/access.class.php'; 
require_once 'lib/ui.lib.php';
require_once 'lib/log.lib.php'; 
require_once 'modules/horde/Browser.php';
require_once 'lib/install.php';
require_once 'lib/debug.lib.php';
require_once 'lib/gettext.php';

if ($_SERVER['HTTPS'] == 'on') { $http_type = "https://"; }
else { $http_type = "http://"; }

$prefix = dirname(__FILE__); 
Config::set('prefix',$prefix,'1'); 
$configfile = "$prefix/config/ampache.cfg.php";

set_error_handler('ampache_error_handler');

/* First things first we must be sure that they actually still need to 
   install ampache 
*/
if (!install_check_status($configfile)) { 
	Error::display('general'); 
	exit; 
}

// Define that we are doing an install so the includes will work
define('INSTALL','1'); 
define('INIT_LOADED','1');

/* Clean up incomming variables */
$web_path = scrub_in($_REQUEST['web_path']);
$username = scrub_in($_REQUEST['local_username']);
$password = $_REQUEST['local_pass'];
$hostname = scrub_in($_REQUEST['local_host']);
$database = scrub_in($_REQUEST['local_db']);
if ($_SERVER['HTTPS'] == 'on') { $http_type = "https://"; }
else { $http_type = "http://"; }

define('WEB_PATH',$http_type . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . '/' . basename($_SERVER['PHP_SELF']));
define('WEB_ROOT',$http_type . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF'])); 

/* Catch the Current Action */
switch ($_REQUEST['action']) { 
	case 'create_db':
		/* Get the variables for the language */
		$htmllang = $_REQUEST['htmllang'];
		$charset  = $_REQUEST['charset'];

		// Set the lang in the conf array
		Config::set('lang', $htmllang,'1');
		Config::set('site_charset', $charset, '1');
		load_gettext();

		if (!install_insert_db($username,$password,$hostname,$database)) {
			require_once 'templates/show_install.inc.php';
			break;
		}

		// Now that it's inserted save the lang preference
		Preference::update('lang','-1',$htmllang);
		
		header ("Location: " . WEB_PATH . "?action=show_create_config&local_db=$database&local_host=$hostname&htmllang=$htmllang&charset=$charset");
		
	break;
	case 'create_config':

		// Test and make sure that the values they give us actually work
		if (!check_database($hostname,$username,$password)) { 
			Error::add('config',_('Error: Unable to make Database Connection') . mysql_error());
		} 

		if (!Error::occurred()) { 
			$created_config = install_create_config($web_path,$username,$password,$hostname,$database);
		} 

		require_once 'templates/show_install_config.inc.php';
	break;
	case 'show_create_config':
	
                /* Attempt to Guess the Web_path */
		$web_path = dirname($_SERVER['PHP_SELF']);
		$web_path = rtrim($web_path,"\/");

		/* Get the variables for the language */
		$htmllang = $_REQUEST['htmllang'];
		$charset  = $_REQUEST['charset'];

		// Set the lang in the conf array
		Config::set('lang',$htmllang,'1');

		// We need the charset for the different languages
		$charsets = array('de_DE' => 'ISO-8859-15',
				  'en_US' => 'iso-8859-1',
				  'en_GB' => 'UTF-8',
				  'ja_JP' => 'UTF-8',
				  'es_ES' => 'iso-8859-1',
				  'fr_FR' => 'iso-8859-1',
				  'el_GR' => 'el_GR.utf-8',
				  'it_IT' => 'UTF-8',
				  'nl_NL' => 'ISO-8859-15',
				  'tr_TR' => 'iso-8859-9',
				  'zh_CN' => 'GBK');
		$charset = $charsets[$_REQUEST['htmllang']];
		
		// Set the site_charset in the conf array
		Config::set('site_charset',$charsets[$_REQUEST['htmllang']],'1');
		
		/* load_gettext mojo */
		load_gettext();
		header ("Content-Type: text/html; charset=" . Config::get('site_charset'));
		
		require_once 'templates/show_install_config.inc.php';
	break;
	case 'create_account':

		$results = parse_ini_file($configfile);
		Config::set_by_array($results,'1');

		/* Get the variables for the language */
		$htmllang = $_REQUEST['htmllang'];
		$charset  = $_REQUEST['charset'];

		// Set the lang in the conf array
		Config::set('lang', $htmllang,'1');
		Config::set('site_charset', $charset, '1');
		load_gettext();

		$password2 = scrub_in($_REQUEST['local_pass2']); 

		if (!install_create_account($username,$password,$password2)) { 
			require_once Config::get('prefix') . '/templates/show_install_account.inc.php';
			break;
		}

		header ("Location: " . WEB_ROOT . "/login.php");
	break;	
	case 'show_create_account':
	
		$results = parse_ini_file($configfile);

		/* Get the variables for the language */
		$htmllang = $_REQUEST['htmllang'];
		$charset  = $_REQUEST['charset'];

		// Set the lang in the conf array
		Config::set('lang', $htmllang,'1');
		Config::set('site_charset', $charset, '1');
		load_gettext();

		/* Make sure we've got a valid config file */
		if (!check_config_values($results)) { 
			Error::add('general',_('Error: Config file not found or Unreadable')); 
			require_once Config::get('prefix') . '/templates/show_install_config.inc.php'; 
			break;
		}

		/* Get the variables for the language */
		$htmllang = $_REQUEST['htmllang'];
		$charset  = $_REQUEST['charset'];
		
		// Set the lang in the conf array
		Config::set('lang',$htmllang,'1');

		// We need the charset for the different languages
		$charsets = array('de_DE' => 'ISO-8859-15',
				  'en_US' => 'iso-8859-1',
				  'ja_JP' => 'UTF-8',
				  'en_GB' => 'UTF-8',
				  'es_ES' => 'iso-8859-1',
				  'fr_FR' => 'iso-8859-1',
				  'it_IT' => 'UTF-8',
				  'nl_NL' => 'ISO-8859-15',
				  'tr_TR' => 'iso-8859-9',
				  'zh_CN' => 'GBK');
		$charset = $charsets[$_REQUEST['htmllang']];
		
		// Set the site_charset in the conf array
		Config::set('site_charset',$charsets[$_REQUEST['htmllang']],'1');
		
		/* load_gettext mojo */
		load_gettext();
		header ("Content-Type: text/html; charset=" . Config::get('site_charset'));
		
		require_once Config::get('prefix') . '/templates/show_install_account.inc.php';
	break;
        case 'init':
		/* First step of installation */
		// Get the language
		$htmllang = $_POST['htmllang'];

		// Set the lang in the conf array
		Config::set('lang',$htmllang,'1');

		// We need the charset for the different languages
		$charsets = array('de_DE' => 'ISO-8859-15',
				  'en_US' => 'iso-8859-1',
				  'cs_CZ' => 'UTF-8',
				  'ja_JP' => 'UTF-8',
				  'en_GB' => 'UTF-8',
				  'es_ES' => 'iso-8859-1',
				  'fr_FR' => 'iso-8859-1',
				  'it_IT' => 'UTF-8',
				  'nl_NL' => 'ISO-8859-15',
				  'tr_TR' => 'iso-8859-9',
				  'zh_CN' => 'GBK');
		$charset = $charsets[$_POST['htmllang']];

		// Set the site_charset in the conf array
 	        Config::set('site_charset',$charsets[$_POST['htmllang']],'1');
			
		// Now we make voodoo with the Load gettext mojo
		load_gettext();

		// Page ready  :)
		header ("Content-Type: text/html; charset=$charset");
		require_once 'templates/show_install.inc.php';
	break;
        default:
		if ($_ENV['LANG']) {
			$lang = $_ENV['LANG'];
		} else {
			$lang = "en_US";
		}
		if(strpos($lang, ".")) {
			$langtmp = split("\.", $lang);
			$htmllang = $langtmp[0];
			$charset = $langtmp[1];
		} else {
			$htmllang = $lang;
			$charset = "UTF-8";
		}
		Config::set('lang',$htmllang,'1');
		Config::set('site_charset', $charset, '1');
		load_gettext();

		/* Show the language options first */
		require_once 'templates/show_install_lang.inc.php';
	break;
} // end action switch

?>
