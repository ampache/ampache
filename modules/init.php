<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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

/*!
	@header INIT file
	Take care of our init grunt work so we don't scatter paths
	all over the place.

*/

// Set the Error level manualy... I'm to lazy to fix notices
error_reporting(E_ALL ^ E_NOTICE);

// This makes this file nolonger need customization
// the config file is in the same dir as this (init.php) file.
$ampache_path = dirname(__FILE__);
$prefix = realpath($ampache_path . "/../");
$configfile = "$prefix/config/ampache.cfg.php";
require_once($prefix . "/lib/general.lib.php");

/*********************STOP EDITING*********************************/

/*
 Check to see if this is Http or https
*/
if ($_SERVER['HTTPS'] == 'on') { 
	$http_type = "https://";
}
else { 
	$http_type = "http://";
}

/*
 See if the Config File Exists if it doesn't
 then go ahead and move them over to the install
 script
*/
if (!file_exists($configfile)) { 
        $path = preg_replace("/(.*)\/(\w+\.php)$/","\${1}", $_SERVER['PHP_SELF']);
	$link = $http_type . $_SERVER['HTTP_HOST'] . $path . "/install.php";
	header ("Location: $link");
	exit();
}

/* 
 Try to read the config file, if it fails give them
 an explanation
*/
if (!$results = read_config($configfile,0)) {
	$path = preg_replace("/(.*)\/(\w+\.php)$/","\${1}", $_SERVER['PHP_SELF']);
	$link = $http_type . $_SERVER['HTTP_HOST'] . $path . "/test.php";
	header ("Location: $link");
	exit();
} 


//FIXME: Until we have a config updater force stream as allowed playback method
if (!$results['allow_stream_playback']) { 
	$results['allow_stream_playback'] = "true";
}


/** This is the version.... fluf nothing more... **/
$results['version']		= '3.3.2-Beta2 (Build 007)';



$results['raw_web_path']	= $results['web_path'];
$results['web_path']		= $http_type . $_SERVER['HTTP_HOST'] . $results['web_path'];
$results['catalog_file_pattern']= 'mp3|mpc|m4p|m4a|mp4|aac|ogg|rm|wma|asf|flac|spx|ra';
$results['http_port']		= $_SERVER['SERVER_PORT'];
if (!$results['prefix']) { 
	$results['prefix'] = $prefix;
}
if (!$results['stop_auth']) {
        $results['stop_auth'] = $results['prefix'] . "/modules/vauth/gone.fishing";
}
if (!$results['http_port']) { 
	$results['http_port']	= '80';
} 
if (!$results['site_charset']) { 
	$results['site_charset'] = "iso-8859-1";
}
if (!$results['log_path']) { 
	$results['log_path']		= '/tmp';
}
if (!$results['ellipse_threshold_album']) { 
	$results['ellipse_threshold_album'] = 27;
}
if (!$results['ellipse_threshold_artist']) { 
	$results['ellipse_threshold_artist'] = 27;
}
if (!$results['ellipse_threshold_title']) { 
	$results['ellipse_threshold_title'] = 27;
}
if (!$results['raw_web_path']) { 
	$results['raw_web_path'] = '/';
}
if (!$_SERVER['SERVER_NAME']) { 
	$_SERVER['SERVER_NAME'] = '';
}

/* Variables needed for vauth Module */
$results['cookie_path'] 	= $results['raw_web_path'];
$results['cookie_domain']	= $_SERVER['SERVER_NAME'];
$results['cookie_life']		= $results['sess_cookielife'];
$results['session_name']	= $results['sess_name'];
$results['cookie_secure']	= '0';
$results['session_length']	= '9000';
$results['mysql_password']	= $results['local_pass'];
$results['mysql_username']	= $results['local_username'];
$results['mysql_hostname']	= $results['local_host'];
$results['mysql_db']		= $results['local_db'];

/* Temp Fixes */
$results = fix_preferences($results);

// Setup Static Arrays
conf($results);

// Vauth Requires
require_once(conf('prefix') . '/modules/vauth/init.php');

// Librarys
require_once(conf('prefix') . '/lib/album.lib.php');
require_once(conf('prefix') . '/lib/artist.lib.php');
require_once(conf('prefix') . '/lib/song.php');
require_once(conf('prefix') . '/lib/search.php');
require_once(conf('prefix') . '/lib/preferences.php');
require_once(conf('prefix') . '/lib/rss.php');
require_once(conf('prefix') . '/lib/log.lib.php');
require_once(conf('prefix') . '/lib/mpd.php');
require_once(conf('prefix') . '/lib/ui.lib.php');
require_once(conf('prefix') . '/lib/gettext.php');
require_once(conf('prefix') . '/lib/batch.lib.php');
require_once(conf('prefix') . '/lib/themes.php');
require_once(conf('prefix') . '/lib/stream.lib.php');
require_once(conf('prefix') . '/lib/playlist.lib.php');
require_once(conf('prefix') . '/modules/lib.php');
require_once(conf('prefix') . '/modules/admin.php');
require_once(conf('prefix') . '/modules/catalog.php');
require_once(conf('prefix') . '/lib/upload.php');

// Modules (These are conditionaly included depending upon config values)
require_once(conf('prefix') . "/modules/id3/audioinfo.class.php");
require_once(conf('prefix') . "/modules/amazon/Snoopy.class.php");
require_once(conf('prefix') . "/modules/amazon/AmazonSearchEngine.class.php");
require_once(conf('prefix') . "/lib/xmlrpc.php");
require_once(conf('prefix') . "/modules/xmlrpc/xmlrpc.inc");

if (conf('allow_slim_playback')) { 
	require_once(conf('prefix') . '/modules/slimserver/slim.class.php');
}

if (conf('allow_mpd_playback')) { 
	require_once(conf('prefix') . '/modules/mpd/mpd.class.php');		
}

if (conf('ratings')) { 
	require_once(conf('prefix') . '/lib/class/rating.class.php');
	require_once(conf('prefix') . '/lib/rating.lib.php');
}

// Classes
require_once(conf('prefix') . '/lib/class/catalog.class.php');
require_once(conf('prefix') . '/lib/class/stream.class.php');
require_once(conf('prefix') . '/lib/class/playlist.class.php');
require_once(conf('prefix') . '/lib/class/song.class.php');
require_once(conf('prefix') . '/lib/class/view.class.php');
require_once(conf('prefix') . '/lib/class/update.class.php');
require_once(conf('prefix') . '/lib/class/user.class.php');
require_once(conf('prefix') . '/lib/class/album.class.php');
require_once(conf('prefix') . '/lib/class/artist.class.php');
require_once(conf('prefix') . '/lib/class/access.class.php');
require_once(conf('prefix') . '/lib/class/error.class.php');
require_once(conf('prefix') . '/lib/class/genre.class.php');
require_once(conf('prefix') . '/lib/class/flag.class.php');

/* Set a new Error Handler */
$old_error_handler = set_error_handler("ampache_error_handler");

/* Initilize the Vauth Library */
vauth_init($results);

/* Check their PHP Vars to make sure we're cool here */
if ($results['memory_limit'] < 16) { 
	$results['memory_limit'] = 16;
}
set_memory_limit($results['memory_limit']);

if (ini_get('short_open_tag') != "On") { 
	ini_set (short_open_tag, "On");
}

// Check Session GC mojo, increase if need be
$gc_probability = @ini_get('session.gc_probability');
$gc_divisor     = @ini_get('session.gc_divisor');

if (!$gc_divisor) { 
	$gc_divisor = '100';
}
if (!$gc_probability) { 
	$gc_probability = '1';
}

// Force GC on 1:5 page loads 
if (($gc_divisor / $gc_probability) > 5) {
	$new_gc_probability = $gc_divisor * .2;
	ini_set('session.gc_probability',$new_gc_probability);
}
/* END Set PHP Vars */

/* Overwrite them with the DB preferences */
set_site_preferences();

/* Seed the random number */
srand((double) microtime() * 1000003);

// If we don't want a session
if (!isset($no_session) AND conf('use_auth')) { 
	if (!vauth_check_session()) { logout(); exit(); }
	init_preferences();
	set_theme();	
	$user = new User($_SESSION['userdata']['username']);
	$user->set_preferences();
	$user->update_last_seen();
}
elseif (!conf('use_auth')) { 
	$auth['success'] = 1;
	$auth['username'] = '-1';
	$auth['fullname'] = "Ampache User";
	$auth['id'] = -1;
	$auth['access'] = "admin";
	$auth['offset_limit'] = 50;
	if (!vauth_check_session()) { vauth_session_create($auth); }
	$user 			= new User(-1);
	$user->fullname 	= $auth['fullname'];
	$user->offset_limit 	= $auth['offset_limit'];
	$user->username 	= $auth['username'];
	$user->access 		= $auth['access'];
	$_SESSION['userdata']['username'] 	= $auth['username'];
	$user->set_preferences();
	init_preferences();
	set_theme();
}
else { 
	$user = new user();
}

// Load gettext mojo
load_gettext();

/* Set CHARSET */
header ("Content-Type: text/html; charset=" . conf('site_charset'));

/* Clean up a bit */
unset($array);
unset($results);

/* Setup the flip class */
flip_class(array('odd','even')); 

/* Setup the Error Class */
$error = new Error();
$theme = get_theme(conf('theme_name'));

if (! preg_match('/update\.php/', $_SERVER['PHP_SELF'])) {
	$update = new Update();
	if ($update->need_update()) {
		header("Location: " . conf('web_path') . "/update.php");
		exit();
	}
}

unset($update);
?>
