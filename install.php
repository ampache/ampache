<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Install
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2012 Ampache.org All Rights Reserved
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
 * @package	Ampache
 * @copyright	2001 - 2012 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

$prefix = dirname(__FILE__);
require_once $prefix . '/lib/init-tiny.php';
require_once $prefix . '/lib/install.lib.php';

set_error_handler('ampache_error_handler');

/* First things first we must be sure that they actually still need to
   install ampache
*/
if (!install_check_status($configfile)) {
	$redirect_url = "login.php";
	require_once Config::get('prefix') . '/templates/error_page.inc.php';
	exit;
}

define('INSTALL','1');

/* Clean up incoming variables */
$web_path = scrub_in($_REQUEST['web_path']);
$username = scrub_in($_REQUEST['local_username']);
$password = $_REQUEST['local_pass'];
$hostname = scrub_in($_REQUEST['local_host']);
$database = scrub_in($_REQUEST['local_db']);

// Correct potential \ or / in the dirname
$safe_dirname = rtrim(dirname($_SERVER['PHP_SELF']),"/\\"); 

define('WEB_PATH',$http_type . $_SERVER['HTTP_HOST'] . $safe_dirname . '/' . basename($_SERVER['PHP_SELF']));
define('WEB_ROOT',$http_type . $_SERVER['HTTP_HOST'] . $safe_dirname);

unset($safe_dirname); 

/* Catch the Current Action */
switch ($_REQUEST['action']) {
	case 'create_db':
		/* Get the variables for the language */
		$htmllang = $_REQUEST['htmllang'];
		$charset  = $_REQUEST['charset'];

		// Set the lang in the conf array
		Config::set('lang', $htmllang, true);
		Config::set('site_charset', $charset, true);
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

		$htmllang = $_REQUEST['htmllang'];
		$charset  = $_REQUEST['charset'];
		// Test and make sure that the values they give us actually work
		if (!check_database($hostname,$username,$password)) {
			Error::add('config',_('Error: Unable to make Database Connection') . mysql_error());
		}

		// Was download pressed?
		$download = (!isset($_POST['write']));

		if (!Error::occurred()) {
			$created_config = install_create_config($web_path,$username,$password,$hostname,$database,$download);
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
		Config::set('lang', $htmllang, true);

		// We need the charset for the different languages
		$charsets = array(
				  'ar_SA' => 'UTF-8',
				  'de_DE' => 'ISO-8859-15',
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
		Config::set('site_charset', $charsets[$_REQUEST['htmllang']], true);

		/* load_gettext mojo */
		load_gettext();
		header ("Content-Type: text/html; charset=" . Config::get('site_charset'));

		require_once 'templates/show_install_config.inc.php';
	break;
	case 'create_account':

		$results = parse_ini_file($configfile);
		Config::set_by_array($results, true);

		/* Get the variables for the language */
		$htmllang = $_REQUEST['htmllang'];
		$charset  = $_REQUEST['charset'];

		// Set the lang in the conf array
		Config::set('lang', $htmllang, true);
		Config::set('site_charset', $charset, true);
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
		Config::set('lang', $htmllang, true);
		Config::set('site_charset', $charset, true);
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
		Config::set('lang', $htmllang, true);

		// We need the charset for the different languages
		$charsets = array(
				  'ar_SA' => 'UTF-8',
				  'de_DE' => 'ISO-8859-15',
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
		Config::set('site_charset', $charsets[$_REQUEST['htmllang']], true);

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
		Config::set('lang', $htmllang, true);

		// We need the charset for the different languages
		$charsets = array(
				  'ar_SA' => 'UTF-8',
				  'de_DE' => 'ISO-8859-15',
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
 	        Config::set('site_charset', $charsets[$_POST['htmllang']], true);

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
			$langtmp = explode(".", $lang);
			$htmllang = $langtmp[0];
			$charset = $langtmp[1];
		} else {
			$htmllang = $lang;
			$charset = "UTF-8";
		}
		Config::set('lang', $htmllang, true);
		Config::set('site_charset', $charset, true);
		load_gettext();

		/* Show the language options first */
		require_once 'templates/show_install_lang.inc.php';
	break;
} // end action switch

?>
