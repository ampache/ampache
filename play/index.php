<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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
require_once '../lib/init.php';
require_once Config::get('prefix') . '/modules/horde/Browser.php';
ob_end_clean(); 

/* These parameters had better come in on the url. */
$uid 		= scrub_in($_REQUEST['uid']);
$song_id 	= scrub_in($_REQUEST['song']);
$sid 		= scrub_in($_REQUEST['sid']);

/* This is specifically for tmp playlist requests */
$demo_id	= scrub_in($_REQUEST['demo_id']);
$random		= scrub_in($_REQUEST['random']); 

/* First things first, if we don't have a uid/song_id stop here */
if (empty($song_id) && empty($demo_id) && empty($random)) { 
	debug_event('no_song',"Error: No Song UID Specified, nothing to play",'2');
	exit; 
}

if (!isset($uid)) { 
	debug_event('no_user','Error: No User specified','2'); 
	exit;
}

/* Misc Housework */
$user = new User($uid);

/* If the user has been disabled (true value) */
if (make_bool($GLOBALS['user']->disabled)) {
	debug_event('user_disabled',"Error $user->username is currently disabled, stream access denied",'3');
	echo "Error: User Disabled"; 
	exit; 
}

// If we're doing XML-RPC check _GET
if (Config::get('xml_rpc')) { 
	$xml_rpc = $_GET['xml_rpc'];
}

// If require session is set then we need to make sure we're legit
if (Config::get('require_session')) { 
	if(!Stream::session_exists($sid) && !Access::session_exists(array(),$sid,'api')) {	
		debug_event('session_expired',"Streaming Access Denied: " . $GLOBALS['user']->username . "'s session has expired",'3');
    		die(_("Session Expired: please log in again at") . " " . Config::get('web_path') . "/login.php");
	}

	// Now that we've confirmed the session is valid
	// extend it
	Stream::extend_session($sid,$uid);
}


/* Update the users last seen information */
$user->update_last_seen();

/* If we are in demo mode.. die here */
if (Config::get('demo_mode') || (!$GLOBALS['user']->has_access('25') && !$xml_rpc) ) {
	debug_event('access_denied',"Streaming Access Denied:" .Config::get('demo_mode') . "is the value of demo_mode. Current user level is " . $GLOBALS['user']->access,'3');
	access_denied();
	exit; 
}

/* 
   If they are using access lists let's make sure 
   that they have enough access to play this mojo
*/
if (Config::get('access_control')) { 
	if (!Access::check_network('stream',$_SERVER['REMOTE_ADDR'],$GLOBALS['user']->id,'25') AND
		!Access::check_network('network',$_SERVER['REMOTE_ADDR'],$GLOBALS['user']->id,'25')) { 
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
if ($demo_id) { 
	$democratic = new Democratic($demo_id);
	/* This takes into account votes etc and removes the */
	$song_id = $democratic->get_next_object();
}

/**
 * if we are doing random let's pull the random object
 */
if ($random) { 
	$song_id = Random::get_single_song($_REQUEST['type']); 
} 

/* Base Checks passed create the song object */
$song = new Song($song_id);
$song->format();
$catalog = new Catalog($song->catalog);

/* If the song is disabled */
if (!make_bool($song->enabled)) { 
	debug_event('song_disabled',"Error: $song->file is currently disabled, song skipped",'5');
	exit;
}

/* If we don't have a file, or the file is not readable */
if (!$song->file OR ( !is_readable($song->file) AND $catalog->catalog_type != 'remote' ) ) { 

	// We need to make sure this isn't democratic play, if it is then remove the song
	// from the vote list
	if (is_object($tmp_playlist)) { 
		$tmp_playlist->delete_track($song_id); 
	}

	debug_event('file_not_found',"Error song $song->file ($song->title) does not have a valid filename specified",'2');
	echo "Error: Invalid Song Specified, file not found or file unreadable"; 
	exit; 
}

	
/* Run Garbage Collection on Now Playing */
gc_now_playing();

// If we are running in Legalize mode, don't play songs already playing
if (Config::get('lock_songs')) {
	if (!check_lock_songs($song->id)) { exit(); }
}

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



// make fread binary safe
set_magic_quotes_runtime(0);

// don't abort the script if user skips this song because we need to update now_playing
ignore_user_abort(TRUE);

// Format the song name
$song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;

/* If they are just trying to download make sure they have rights 
 * and then present them with the download file
 */
if ($_GET['action'] == 'download' AND $GLOBALS['user']->prefs['download']) { 
	
	// STUPID IE
	$song_name = str_replace(array('?','/','\\'),"_",$song_name);

	// Use Horde's Browser class to send the headers
	header("Content-Length: " . $song->size); 
	$browser = new Browser(); 
	$browser->downloadHeaders($song_name,$song->mime,false,$song->size); 
	$fp = fopen($song->file,'rb'); 

	if (!is_resource($fp)) { 
                debug_event('file_read_error',"Error: Unable to open $song->file for downloading",'2');
		exit(); 
        }
		
	// Check to see if we should be throttling because we can get away with it
	if ($GLOBALS['user']->prefs['rate_limit'] > 0) { 
		while (!feof($fp)) { 
			echo fread($fp,round($GLOBALS['user']->prefs['rate_limit']*1024)); 
			flush(); 
			sleep(1); 
		} 
	} 
	else { 
		fpassthru($fp); 
	} 
		
	fclose($fp); 
	exit(); 

} // if they are trying to download and they can

	
$startArray = sscanf( $_SERVER[ "HTTP_RANGE" ], "bytes=%d-" );
$start = $startArray[0];

// Generate browser class for sending headers
$browser = new Browser();
header("Accept-Ranges: bytes" );

// Prevent the script from timing out
set_time_limit(0);			

/* We're about to start record this persons IP */
if (Config::get('track_user_ip')) { 
	$user->insert_ip_history();
}

/* If access control is on and they aren't local, downsample! */
if (Config::get('access_control') AND Config::get('downsample_remote')) { 
	if (Access::check_network('network',$_SERVER['REMOTE_ADDR'],$GLOBALS['user']->id,'25')) { 
		$not_local = true;
	}
} // if access_control

// If they are downsampling, or if the song is not a native stream or it's non-local
if (($GLOBALS['user']->prefs['transcode'] == 'always' || !$song->native_stream() || $not_local) && $GLOBALS['user']->prefs['transcode'] != 'never') { 
	debug_event('downsample','Starting Downsample...','5');
	$fp = Stream::start_downsample($song,$lastid,$song_name);
} // end if downsampling
else { 
	// Send file, possible at a byte offset
	$fp = fopen($song->file, 'rb');
	
	if (!is_resource($fp)) { 
		debug_event('file_read_error',"Error: Unable to open $song->file for reading",'2');
		cleanup_and_exit($lastid);
	}
} // else not downsampling

// Put this song in the now_playing table
insert_now_playing($song->id,$uid,$song->time,$sid);

if ($start) {
	debug_event('seek','Content-Range header recieved, skipping ahead ' . $start . ' bytes out of ' . $song->size,'5');
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
 */
$bytesStreamed = 0;
$minBytesStreamed = $song->size / 2;

// Actually do the streaming 
do { 
	$buf = fread($fp, 2048);
        print($buf);
        $bytesStreamed += 2048;
} while (!feof($fp) && (connection_status() == 0));

// Make sure that a good chunk of the song has been played
if ($bytesStreamed > $minBytesStreamed) {
	debug_event('Stats','Registering stats for ' . $song->title,'5'); 
	
        $user->update_stats($song->id);
	/* If this is a voting tmp playlist remove the entry */
	if (is_object($democratic)) { 
		$row_id = $democratic->get_uid_from_object_id($song_id,'song'); 
		$democratic->delete_votes($row_id);
	} // if tmp_playlist
	
	/* Set the Song as Played if it isn't already */
	$song->set_played();

} // if enough bytes are streamed
else { 
	debug_event('stream',$bytesStreamed .' of ' . $song->size . ' streamed, less than ' . $minBytesStreamed . ' not collecting stats','5'); 
} 


/* Clean up any open ends */
if ($GLOBALS['user']->prefs['play_type'] == 'downsample' || !$song->native_stream()) { 
	@pclose($fp);
} 
else { 
	@fclose($fp);
}

// Note that the stream has ended
debug_event('stream','Stream Ended at ' . $bytesStreamed . ' bytes out of ' . $song->size,'5'); 

?>
