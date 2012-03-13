<?php
/* vim:set tabstop=8 softtabstop=8 shiftwidth=8 noexpandtab: */
/**
 * Init Library
 *
 *
 * LICENSE: GNU General Public License, version 2 (GPLv2)
 * Copyright (c) 2001 - 2011 Ampache.org All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License v2
 * as published by the Free Software Foundation
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
 * @copyright	2001 - 2011 Ampache.org
 * @license	http://opensource.org/licenses/gpl-2.0 GPLv2
 * @link	http://www.ampache.org/
 */

// Use output buffering, this gains us a few things and
// fixes some CSS issues
ob_start();

$ampache_path = dirname(__FILE__);
$prefix = realpath($ampache_path . "/../");
require_once $prefix . '/lib/init-tiny.php';

// Explicitly load vauth and enable the custom session handler.
// Relying on autoload may not always load it before sessiony things are done.
require_once $prefix . '/lib/class/vauth.class.php';
vauth::_auto_init();

// Set up for redirection on important error cases
$path = preg_replace('#(.*)/(\w+\.php)$#', '$1', $_SERVER['PHP_SELF']);
$path = $http_type . $_SERVER['HTTP_HOST'] . $path;

// Check to make sure the config file exists. If it doesn't then go ahead and 
// send them over to the install script.
if (!file_exists($configfile)) {
	$link = $path . '/install.php';
}
else {
	// Make sure the config file is set up and parsable
	$results = @parse_ini_file($configfile);

	if (!count($results)) {
		$link = $path . '/test.php?action=config';
	}
}

// Verify that a few important but commonly disabled PHP functions exist and
// that we're on a usable version
if (!function_exists('hash') || !function_exists('inet_pton') || (floatval(phpversion()) < 5.3)) {
	$link = $path . '/test.php';
}

// Do the redirect if we can't continue
if ($link) {
	header ("Location: $link");
	exit();
}

/** This is the version.... fluf nothing more... **/
$results['version']		= '3.6-Alpha1-DEV';
$results['int_config_version']	= '11';

$results['raw_web_path']	= $results['web_path'];
$results['web_path']		= $http_type . $_SERVER['HTTP_HOST'] . $results['web_path'];
if (isset($_SERVER['HTTP_X_FORWARDED_PORT'])) {
   $results['http_port']   = $_SERVER['HTTP_X_FORWARDED_PORT'];
} else {
   $results['http_port']   = $_SERVER['SERVER_PORT'];
}
if (!$results['http_port']) {
	$results['http_port']	= '80';
}
if (!$results['site_charset']) {
	$results['site_charset'] = "UTF-8";
}
if (!$results['raw_web_path']) {
	$results['raw_web_path'] = '/';
}
if (!$_SERVER['SERVER_NAME']) {
	$_SERVER['SERVER_NAME'] = '';
}
if (isset($results['user_ip_cardinality']) && !$results['user_ip_cardinality']) {
	$results['user_ip_cardinality'] = 42;
}

/* Variables needed for vauth class */
$results['cookie_path'] 	= $results['raw_web_path'];
$results['cookie_domain']	= $_SERVER['SERVER_NAME'];
$results['cookie_life']		= $results['session_cookielife'];
$results['cookie_secure']	= $results['session_cookiesecure'];

// Library and module includes we can't do with the autoloader
require_once $prefix . '/modules/getid3/getid3.php';
require_once $prefix . '/modules/nusoap/nusoap.php';
require_once $prefix . '/modules/phpmailer/class.phpmailer.php';
require_once $prefix . '/modules/phpmailer/class.smtp.php';
require_once $prefix . '/modules/infotools/Snoopy.class.php';
require_once $prefix . '/modules/infotools/AmazonSearchEngine.class.php';
require_once $prefix . '/modules/infotools/lastfm.class.php';
require_once $prefix . '/modules/php_musicbrainz/mbQuery.php';
require_once $prefix . '/modules/ampacheapi/AmpacheApi.lib.php';

/* Temp Fixes */
$results = Preference::fix_preferences($results);

Config::set_by_array($results, true);

// Modules (These are conditionally included depending upon config values)
if (Config::get('ratings')) {
	require_once $prefix . '/lib/rating.lib.php';
}

/* Set a new Error Handler */
$old_error_handler = set_error_handler('ampache_error_handler');

/* Check their PHP Vars to make sure we're cool here */
$post_size = @ini_get('post_max_size');
if (substr($post_size,strlen($post_size)-1,strlen($post_size)) != 'M') {
	/* Sane value time */
	ini_set('post_max_size','8M');
}

// In case the local setting is 0
ini_set('session.gc_probability','5');

if (! isset($results['memory_limit']) || $results['memory_limit'] < 24) {
	$results['memory_limit'] = 24;
}

set_memory_limit($results['memory_limit']);

/**** END Set PHP Vars ****/

// If we want a session
if (!defined('NO_SESSION') && Config::get('use_auth')) {
	/* Verify their session */
	if (!vauth::session_exists('interface',$_COOKIE[Config::get('session_name')])) { vauth::logout($_COOKIE[Config::get('session_name')]); exit; }

	// This actually is starting the session
	vauth::check_session();

	/* Create the new user */
	$GLOBALS['user'] = User::get_from_username($_SESSION['userdata']['username']);

	/* If the user ID doesn't exist deny them */
	if (!$GLOBALS['user']->id AND !Config::get('demo_mode')) { vauth::logout(session_id()); exit; }

	/* Load preferences and theme */
	$GLOBALS['user']->update_last_seen();
}
elseif (!Config::get('use_auth')) {
	$auth['success'] = 1;
	$auth['username'] = '-1';
	$auth['fullname'] = "Ampache User";
	$auth['id'] = -1;
	$auth['offset_limit'] = 50;
	$auth['access'] = Config::get('default_auth_level') ? User::access_name_to_level(Config::get('default_auth_level')) : '100';
	if (!vauth::session_exists('interface',$_COOKIE[Config::get('session_name')])) {
		vauth::create_cookie();
		vauth::session_create($auth);
		vauth::check_session();
		$GLOBALS['user'] = new User($auth['username']);
		$GLOBALS['user']->username = $auth['username'];
		$GLOBALS['user']->fullname = $auth['fullname'];
		$GLOBALS['user']->access = $auth['access'];
	}
	else {
		vauth::check_session();
		if ($_SESSION['userdata']['username']) {
			$GLOBALS['user'] = User::get_from_username($_SESSION['userdata']['username']);
		}
		else {
			$GLOBALS['user'] = new User($auth['username']);
			$GLOBALS['user']->id = '-1';
			$GLOBALS['user']->username = $auth['username'];
			$GLOBALS['user']->fullname = $auth['fullname'];
			$GLOBALS['user']->access = $auth['access'];
		}
		if (!$GLOBALS['user']->id AND !Config::get('demo_mode')) { vauth::logout(session_id()); exit; }
		$GLOBALS['user']->update_last_seen();
	}
}
// If Auth, but no session is set
else {
	if (isset($_REQUEST['sid'])) {
		session_name(Config::get('session_name'));
		session_id(scrub_in($_REQUEST['sid']));
		session_start();
		$GLOBALS['user'] = new User($_SESSION['userdata']['uid']);
	}
	else {
		$GLOBALS['user'] = new User();
	}

} // If NO_SESSION passed

// Load the Preferences from the database
Preference::init();

if (session_id()) {
	vauth::session_extend(session_id());
	// We only need to create the tmp playlist if we have a session
	$GLOBALS['user']->load_playlist();
}

/* Add in some variables for ajax done here because we need the user */
Config::set('ajax_url', Config::get('web_path') . '/server/ajax.server.php', true);

// Load gettext mojo
load_gettext();

/* Set CHARSET */
header ("Content-Type: text/html; charset=" . Config::get('site_charset'));

/* Clean up a bit */
unset($array);
unset($results);

/* Check to see if we need to perform an update */
if (!defined('OUTDATED_DATABASE_OK')) {
	if (Update::need_update()) {
		header("Location: " . Config::get('web_path') . "/update.php");
		exit();
	}
}
// For the XMLRPC stuff
$GLOBALS['xmlrpc_internalencoding'] = Config::get('site_charset');

// If debug is on GIMMIE DA ERRORS
if (Config::get('debug')) {
	error_reporting(E_ALL);
}
?>
