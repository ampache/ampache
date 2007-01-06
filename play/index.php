<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
/*

 This is the wrapper for opening music streams from this server.  This script
   will play the local version or redirect to the remote server if that be
   the case.  Also this will update local statistics for songs as well.
   This is also where it decides if you need to be downsampled. 
*/

define('NO_SESSION','1');
require_once('../lib/init.php');
require_once(conf('prefix') . '/modules/horde/Browser.php');


/* These parameters had better come in on the url. */
$uid 		= scrub_in($_REQUEST['uid']);
$song_id 	= scrub_in($_REQUEST['song']);
$sid 		= scrub_in($_REQUEST['sid']);

/* This is specifically for tmp playlist requests */
$tmp_id		= scrub_in($_REQUEST['tmp_id']);

/* First things first, if we don't have a uid/song_id stop here */
if (empty($song_id) && empty($tmp_id)) { 
	debug_event('no_song',"Error: No Song UID Specified, nothing to play",'2');
	exit; 
}

if (!isset($uid)) { 
	debug_event('no_usre','Error: No User specified','2'); 
	exit;
}

/* Misc Housework */
$dbh = dbh();
$user = new User($uid);

if (conf('xml_rpc')) { 
	$xml_rpc = $_GET['xml_rpc'];
}

if (conf('require_session') OR $xml_rpc) { 
	if(!session_exists($sid,$xml_rpc)) {	
		debug_event('session_expired',"Streaming Access Denied: " . $GLOBALS['user']->username . "'s session has expired",'3');
    		die(_("Session Expired: please log in again at") . " " . conf('web_path') . "/login.php");
	}

	// Now that we've confirmed the session is valid
	// extend it
	extend_session($sid);
}

/* If we are in demo mode.. die here */
if (conf('demo_mode') || (!$GLOBALS['user']->has_access('25') && !$xml_rpc) ) {
	debug_event('access_denied',"Streaming Access Denied:" .conf('demo_mode') . "is the value of demo_mode. Current user level is " . $GLOBALS['user']->access,'3');
	access_denied();
}

/* 
   If they are using access lists let's make sure 
   that they have enough access to play this mojo
*/
if (conf('access_control')) { 
	$access = new Access(0);
	if (!$access->check('stream',$_SERVER['REMOTE_ADDR'],$GLOBALS['user']->username,'25') AND
		!$access->check('network',$_SERVER['REMOTE_ADDR'],$GLOBALS['user']->username,'25')) { 
		debug_event('access_denied', "Streaming Access Denied: " . $_SERVER['REMOTE_ADDR'] . " does not have stream level access",'3');
		access_denied();
		exit; 
	}
} // access_control is enabled

/** 
 * If we've got a tmp playlist then get the
 * current song, and do any other crazyness
 * we need to 
 */
if ($tmp_id) { 
	$tmp_playlist = new tmpPlaylist($tmp_id);
	/* This takes into account votes etc and removes the */
	$song_id = $tmp_playlist->get_next_object();
}

/* Base Checks passed create the song object */
$song = new Song($song_id);
$song->format_song();
$catalog = new Catalog($song->catalog);

/* If the song is disabled */
if (!make_bool($song->enabled)) { 
	debug_event('song_disabled',"Error: $song->file is currently disabled, song skipped",'5');
	exit;
}

/* If the user has been disabled (true value) */
if (make_bool($GLOBALS['user']->disabled)) {
	debug_event('user_disabled',"Error $user->username is currently disabled, stream access denied",'3');
	echo "Error: User Disabled"; 
	exit; 
}

/* If we don't have a file, or the file is not readable */
if (!$song->file OR ( !is_readable($song->file) AND $catalog->catalog_type != 'remote' ) ) { 

	// We need to make sure this isn't democratic play, if it is then remove the song
	// from the vote list
	if (is_object($tmp_playlist)) { 
		$tmp_playlist->delete_track($song_id); 
	}

	debug_event('file_not_found',"Error song ($song->title) does not have a valid filename specified",'2');
	echo "Error: Invalid Song Specified, file not found or file unreadable"; 
	exit; 
}
	
/* If we're using auth and we can't find a username for this user */
if ( conf('use_auth') AND !$GLOBALS['user']->username AND !$GLOBALS['user']->is_xmlrpc() ) {
	debug_event('user_not_found',"Error $user->username not found, stream access denied",'3');
	echo "Error: No User Found"; 
	exit; 
}

/* Create the catalog object so we know a little more about it */
$catalog = new Catalog($song->catalog);

/* Update the users last seen information */
$GLOBALS['user']->update_last_seen();

/* Run Garbage Collection on Now Playing */
gc_now_playing();

/* Check to see if this is a 'remote' catalog */
if ($catalog->catalog_type == 'remote') {
	// redirect to the remote host's play path
	/* Break Up the Web Path */
	preg_match("/http:\/\/([^\/]+)\/*(.*)/", conf('web_path'), $match);
	$server = rawurlencode($match[1]);
	$path	= rawurlencode($match[2]);
	$port 	= $_SERVER['SERVER_PORT'];
	if ($_SERVER['HTTPS'] == 'on') { $ssl='1'; }
	else { $ssl = '0'; }  
	$catalog = $catalog->id;
	
	$extra_info = "&xml_rpc=1&xml_path=$path&xml_server=$server&xml_port=$port&ssl=$ssl&catalog=$catalog&sid=$sid";
	header("Location: " . $song->file . $extra_info);
	debug_event('xmlrpc-stream',"Start XML-RPC Stream - " . $song->file . $extra_info,'5');
	exit;
} // end if remote catalog


// If we are running in Legalize mode, don't play songs already playing
if (conf('lock_songs')) {
	if (!check_lock_songs($song->id)) { exit(); }
}

// Put this song in the now_playing table
$lastid = insert_now_playing($song->id,$uid,$song->time);

// make fread binary safe
set_magic_quotes_runtime(0);

// don't abort the script if user skips this song because we need to update now_playing
ignore_user_abort(TRUE);

/* Format the Song Name */
if (conf('stream_name_format')) {
	$song_name = conf('stream_name_format');
	$song_name = str_replace("%basename",basename($song->file),$song_name);
	$song_name = str_replace("%filename",$song->file,$song_name);
	$song_name = str_replace("%type",$song->type,$song_name);
	$song_name = str_replace("%catalog",$catalog->name,$song_name);
	$song_name = str_replace("%A",$song->f_album_full,$song_name); // this and next could be truncated version
	$song_name = str_replace("%a",$song->f_artist_full,$song_name);
	$song_name = str_replace("%C",$catalog->path,$song_name);
	$song_name = str_replace("%c",$song->comment,$song_name);
	$song_name = str_replace("%g",$song->f_genre,$song_name);
	$song_name = str_replace("%T",$song->track,$song_name);
	$song_name = str_replace("%t",$song->title,$song_name);
	$song_name = str_replace("%y",$song->year,$song_name);
} 
else {
	$song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
}
	
$startArray = sscanf( $_SERVER[ "HTTP_RANGE" ], "bytes=%d-" );
$start = $startArray[0];

// Generate browser class for sending headers
$browser = new Browser();
header("Accept-Ranges: bytes" );

// Prevent the script from timing out
set_time_limit(0);			

/* We're about to start record this persons IP */
if (conf('track_user_ip')) { 
	$user->insert_ip_history();
}

/* If access control is on and they aren't local, downsample! */
if (conf('access_control') AND conf('downsample_remote')) { 
	if (!$access->check('network',$_SERVER['REMOTE_ADDR'],$GLOBALS['user']->username,'25')) { 
		$not_local = true;
	}
} // if access_control


	
if ($GLOBALS['user']->prefs['play_type'] == 'downsample' || !$song->native_stream() || $not_local) { 
	debug_event('downsample','Starting Downsample...','5');
	$results = start_downsample($song,$lastid,$song_name);
	$fp = $results['handle'];
	$song->size = $results['size'];
	
} // end if downsampling
else { 
	// Send file, possible at a byte offset
	$fp = fopen($song->file, 'rb');
	
if (!is_resource($fp)) { 
		debug_event('file_read_error',"Error: Unable to open $song->file for reading",'2');
		cleanup_and_exit($lastid);
	}
} // else not downsampling

if ($start) {
	debug_event('seek','Start point recieved, skipping ahead in the song...','5');
	$browser->downloadHeaders($song_name, $song->mime, false, $song->size);
	fseek( $fp, $start );
	$range = $start ."-". ($song->size-1) . "/" . $song->size;
	header("HTTP/1.1 206 Partial Content");
	header("Content-Range: bytes=$range");
	header("Content-Length: ".($song->size-$start));
}

/* Last but not least pump em out */
else {
	debug_event('stream','Starting stream of ' . $song->file . ' with size ' . $song->size,'5'); 
	header("Content-Length: $song->size");
	$browser->downloadHeaders($song_name, $song->mime, false, $song->size);
}
	

/* Let's force them to actually play a portion of the song before 
 * we count it in the statistics
 * @author SH
 */
$bytesStreamed  = 0;
$minBytesStreamed = $song->size / 2;
while (!feof($fp) && (connection_status() == 0)) {
	$buf = fread($fp, 8192);
        print($buf);
        $bytesStreamed += strlen($buf);
}

/* Delete the Now Playing Entry */
delete_now_playing($lastid);

if ($bytesStreamed > $minBytesStreamed) {
        $user->update_stats($song_id);
	/* If this is a voting tmp playlist remove the entry */
	if (is_object($tmp_playlist)) { 
		if ($tmp_playlist->type == 'vote') { 
			$tmp_playlist->delete_track($song_id);
		}
	}
} 

/* Set the Song as Played if it isn't already */
$song->set_played();

/* Clean up any open ends */
if ($GLOBALS['user']->prefs['play_type'] == 'downsample' || !$song->native_stream()) { 
	@pclose($fp);
} 
else { 
	@fclose($fp);
}

?>
