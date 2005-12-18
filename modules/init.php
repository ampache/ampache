<?php
/*

 Copyright (c) 2001 - 2005 Ampache.org
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



// Cheat a little to setup the extra vars needed by libglue

//FIXME: Untile we have a config updater force stream as allowed playback method
if (!$results['conf']['allow_stream_playback']) { 
	$results['conf']['allow_stream_playback'] = "true";
}

$results['conf']['web_path']		= $http_type . $_SERVER['HTTP_HOST'] . $results['conf']['web_path'];
$results['conf']['version']		= '3.3.2-Alpha4 (Build 002)';
$results['conf']['catalog_file_pattern']= 'mp3|mpc|m4p|m4a|mp4|aac|ogg|rm|wma|asf|flac|spx';
$results['libglue']['local_table']	= 'session';
$results['libglue']['local_sid']	= 'id';
$results['libglue']['local_expirecol']	= 'expire';
$results['libglue']['local_usercol']	= 'username';
$results['libglue']['local_typecol']	= 'type';
$results['libglue']['local_datacol']	= 'value';
$results['libglue']['mysql_table']	= 'user';
$results['libglue']['mysql_usercol'] 	= 'username';
$results['libglue']['mysql_passwdcol']	= 'password';
$results['libglue']['local_dbh_name']	= 'local_dbh';
$results['libglue']['auth_methods']	= 'mysql';
$results['libglue']['user_username']	= 'username';
$results['libglue']['mysql_fields']	= 'username,fullname,email,access,offset_limit';
$results['libglue']['mysql_host']	= $results['libglue']['local_host'];
$results['libglue']['mysql_db']		= $results['libglue']['local_db'];
$results['libglue']['mysql_username']	= $results['libglue']['local_username'];
$results['libglue']['mysql_user']	= $results['libglue']['local_username'];
$results['libglue']['mysql_passwd']	= $results['libglue']['local_pass'];
$results['libglue']['mysql_pass']	= $results['libglue']['local_pass'];
$results['libglue']['mysql_passcol']	= 'password';
$results['libglue']['dbh']		= $results['libglue']['local_dbh_name'];
$results['libglue']['auth_page']	= $results['conf']['web_path'];
$results['libglue']['login_page']	= $results['conf']['web_path'] . "/login.php";
$results['conf']['http_port']		= $_SERVER['SERVER_PORT'];
if (!$results['conf']['prefix']) { 
	$results['conf']['prefix'] = $prefix;
}
if (!$results['libglue']['stop_auth']) {
        $results['libglue']['stop_auth'] = $results['conf']['prefix'] . "/modules/libglue/gone.fishing";
}
if (!$results['libglue']['libglue_path']) {
        $results['libglue']['libglue_path']= $results['conf']['prefix'] . "/modules/libglue";
}
if (!$results['conf']['http_port']) { 
	$results['conf']['http_port']	= '80';
} 
if (!$results['conf']['site_charset']) { 
	$results['conf']['site_charset'] = "iso-8859-1";
}
if (!$results['conf']['log_path']) { 
	$results['conf']['log_path']		= '/tmp';
}
if (!$results['conf']['ellipse_threshold_album']) { 
	$results['conf']['ellipse_threshold_album'] = 27;
}
if (!$results['conf']['ellipse_threshold_artist']) { 
	$results['conf']['ellipse_threshold_artist'] = 27;
}
if (!$results['conf']['ellipse_threshold_title']) { 
	$results['conf']['ellipse_threshold_title'] = 27;
}


/* Temp Fixes */
$results['conf'] = fix_preferences($results['conf']);


// Setup Static Arrays
libglue_param($results['libglue']);
conf($results['conf']);

// Libglue Requires
require_once(libglue_param('libglue_path') . "/auth.php");
require_once(libglue_param('libglue_path') . "/session.php");
require_once(libglue_param('libglue_path') . "/dbh.php");

// Librarys
require_once(conf('prefix') . "/lib/album.lib.php");
require_once(conf('prefix') . "/lib/artist.lib.php");
require_once(conf('prefix') . "/lib/song.php");
require_once(conf('prefix') . "/lib/search.php");
require_once(conf('prefix') . "/lib/preferences.php");
require_once(conf('prefix') . "/lib/rss.php");
require_once(conf('prefix') . "/lib/log.lib.php");
require_once(conf('prefix') . "/lib/mpd.php");
require_once(conf('prefix') . "/lib/ui.lib.php");
require_once(conf('prefix') . "/lib/gettext.php");
require_once(conf('prefix') . "/lib/batch.lib.php");
require_once(conf('prefix') . "/lib/themes.php");
require_once(conf('prefix') . "/lib/stream.lib.php");
require_once(conf('prefix') . "/modules/lib.php");
require_once(conf('prefix') . "/modules/admin.php");
require_once(conf('prefix') . "/modules/catalog.php");
require_once(conf('prefix') . "/lib/upload.php");

// Modules (These are conditionaly included depending upon config values)
require_once(conf('prefix') . "/modules/id3/audioinfo.class.php");
require_once(conf('prefix') . "/modules/amazon/Snoopy.class.php");
require_once(conf('prefix') . "/modules/amazon/AmazonSearchEngine.class.php");
require_once(conf('prefix') . "/lib/xmlrpc.php");
require_once(conf('prefix') . "/modules/xmlrpc/xmlrpc.inc");

if (conf('allow_slim_playback')) { 
	require_once(conf('prefix') . "/modules/slimserver/slim.class.php");
}

if (conf('allow_mpd_playback')) { 
	require_once(conf('prefix') . "/modules/mpd/mpd.class.php");		
}

if (conf('allow_xmms2_playback')) { 
	require_once(conf('prefix') . "/modules/xmms2/xmms2.class.php");
}

// Classes
require_once(conf('prefix') . "/lib/class/catalog.class.php");
require_once(conf('prefix') . "/lib/class/stream.class.php");
require_once(conf('prefix') . "/lib/class/playlist.class.php");
require_once(conf('prefix') . "/lib/class/song.class.php");
require_once(conf('prefix') . "/lib/class/view.class.php");
require_once(conf('prefix') . "/lib/class/update.class.php");
require_once(conf('prefix') . "/lib/class/user.class.php");
require_once(conf('prefix') . "/lib/class/album.class.php");
require_once(conf('prefix') . "/lib/class/artist.class.php");
require_once(conf('prefix') . "/lib/class/access.class.php");
require_once(conf('prefix') . "/lib/class/error.class.php");
require_once(conf('prefix') . "/lib/class/genre.class.php");

/* Some Libglue Hacks */
$array['dbh_name'] = 'stupid_pos';
$array['stupid_pos'] = check_sess_db('local');
libglue_param($array);
/*  End Libglue Hacks */

/* Set a new Error Handler */
$old_error_handler = set_error_handler("ampache_error_handler");



/* Check their PHP Vars to make sure we're cool here */
if ($results['conf']['memory_limit'] < 16) { 
	$results['conf']['memory_limit'] = 16;
}
set_memory_limit($results['conf']['memory_limit']);

if (ini_get('short_open_tag') != "On") { 
	ini_set (short_open_tag, "On");
}

// Check Session GC mojo, increase if need be
$gc_probability = @ini_get('session.gc_probability');
$gc_divisor     = @ini_get('session.gc_divisor');

if (!$gc_divisor) { 
	$gc_divisor = '100';
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
	if (!check_session()) { logout(); exit(); }
	get_preferences();
	set_theme();	
	$user = new User($_SESSION['userdata']['username']);
	$user->update_last_seen();
}
if (!conf('use_auth')) { 
	$auth['success'] = 1;
	$auth['info']['username'] = '-1';
	$auth['info']['fullname'] = "Ampache User";
	$auth['info']['id'] = -1;
	$auth['info']['access'] = "admin";
	$auth['info']['offset_limit'] = 50;
	if (!check_session()) { make_local_session_only($auth); }
	$user 			= new User(-1);
	$user->fullname 	= $auth['info']['fullname'];
	$user->offset_limit 	= $auth['info']['offset_limit'];
	$user->username 	= $auth['info']['username'];
	$user->access 		= $auth['info']['access'];
	$_SESSION['userdata']['access'] 	= $auth['info']['access'];
	$_SESSION['userdata']['username'] 	= $auth['info']['username'];
	$_SESSION['userdata']['offset_limit'] 	= $auth['info']['offset_limit'];
	$user->set_preferences();
	get_preferences();
	set_theme();
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

if (! preg_match('/update\.php/', $_SERVER['PHP_SELF'])) {
	$update = new Update();
	if ($update->need_update()) {
		header("Location: " . conf('web_path') . "/update.php");
		exit();
	}
}


unset($update);
?>
