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
/*

 This is the wrapper for opening music streams from this server.  This script
   will play the local version or redirect to the remote server if that be
   the case.  Also this will update local statistics for songs as well.

*/

$no_session = true;
require_once('../modules/init.php');
require_once(conf('prefix') . '/lib/Browser.php');


/* These parameters has better come on the url. */
$uid = htmlspecialchars($_REQUEST['uid']);
$song_id = htmlspecialchars($_REQUEST['song']);
$sid = htmlspecialchars($_REQUEST['sid']);

/* Misc Housework */
$dbh = dbh();
$user = new User($uid);

if (conf('xml_rpc')) { 
	$xml_rpc = $_GET['xml_rpc'];
}

if (conf('require_session')) { 
	if(!session_exists($sid,$xml_rpc)) {	
    		die(_("Session Expired: please log in again at") . " " . conf('web_path') . "/login.php");
	}

	// Now that we've confirmed the session is valid
	// extend it
	extend_session($sid);
}

/* If we are in demo mode.. die here */
if (conf('demo_mode') || !$user->has_access('25')) {
	if (conf('debug')) { 
		log_event($user->username,' access_denied ', "Streaming Access Denied, " . conf('demo_mode') . "is the value of demo_mode. Current user level is $user->access");
	}
	access_denied();
}

/* 
   If they are using access lists let's make sure 
   that they have enough access to play this mojo
*/
if (conf('access_control')) { 

	$access = new Access(0);
	if (!$access->check("25", $_SERVER['REMOTE_ADDR'])) { 
		if (conf('debug')) { 
			log_event($user->username,' access_denied ', "Streaming Access Denied, " . $_SERVER['REMOTE_ADDR'] . " does not have stream level access");
		}
		access_denied();
	}

} // access_control is enabled


// require a uid and valid song
if ( isset( $uid ) ) {
	$song = new Song($song_id);
	$song->format_song();
	$catalog = new Catalog($song->catalog);

	// Create the user object if possible

	if (!$song->file OR ( !is_readable($song->file) AND $catalog->catalog_type != 'remote' ) ) { 
		if (conf('debug')) { 
			log_event($user->username,' file_not_found ',"Error song ($song->title) does not have a valid filename specified");
		}
		echo "Error: No Song"; 
		exit; 
	}
	if ($song->status == '0') { 
		if (conf('debug')) { 
			log_event($user->username,' song_disabled ',"Error: $song->file is currently disabled, song skipped");
		}
		exit;
	}
	if ($user->disabled == '1') {
		if (conf('debug')) { 
			log_event($user->username,' user_disabled ',"Error $user->username is currently disabled, stream access denied");
		}
		echo "Error: User Disabled"; 
		exit; 
	}
	if (!$user->username && !$user->is_xmlrpc()) { 
		if (conf('debug')) { 
			log_event($user->username,' user_not_found ',"Error $user->username not found, stream access denied");
		}
		echo "Error: No User Found"; 
		exit; 
	}

}
else {
	if (conf('debug')) { 
		log_event("Unknown", ' user_not_found ',"Error no UID passed with URL, stream access denied");
	}
	echo "Error: No UID specified";
	exit;
}

$catalog = new Catalog($song->catalog);

if ( $catalog->catalog_type == 'remote' ) {
	// redirect to the remote host's play path
	/* Break Up the Web Path */
	preg_match("/http:\/\/([^\/]+)\/*(.*)/", conf('web_path'), $match);
	$server = rawurlencode($match[1]);
	$path	= rawurlencode($match[2]);
	
	$extra_info = "&xml_rpc=1&xml_path=$path&xml_server=$server&xml_port=80&sid=$sid";
	header("Location: " . $song->file . $extra_info);
}
else {
	if ($user->prefs['play_type'] == 'downsample') { 
		$ds = $user->prefs['sample_rate']; 
	}

	// Update the users last seen
	$user->update_last_seen();

	// Garbage collection for stale entries in the now_playing table
	$time = time();
	$expire = $time - 900;  // 86400 seconds = 1 day
	$sql = "DELETE FROM now_playing WHERE start_time < $expire";
	$db_result = mysql_query($sql, $dbh);

	// If we are running in Legalize mode, don't play songs already playing
	if (conf('lock_songs') == 'true') {
		$sql = "SELECT COUNT(*) FROM now_playing" .
			" WHERE song_id = '$song_id'";
		$db_result = mysql_query($sql, $dbh);
		while ($r = mysql_fetch_row($db_result)) {
			if ($r[0] == 1) {
				// Song is already playing, so exit without returning song
				exit;
			}
		}
	}

	// Put this song in the now_playing table
	$end_time = time() - $song->time;
	$sql = "INSERT INTO now_playing (`song_id`, `user_id`, `start_time`)" .
		" VALUES ('$song_id', '$uid', '$end_time')";
	$db_result = mysql_query($sql, $dbh);
	$lastid = mysql_insert_id($dbh);

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

	

	// Send file, possible at a byte offset
	$fp = @fopen($song->file, 'r');

	if (!is_resource($fp)) { 
		if (conf('debug')) { 
			log_event($user->username,' file_read_error ',"Error: Unable to open $song->file for reading");
		}
		cleanup_and_exit($lastid);
	}

	$startArray = sscanf( $_SERVER[ "HTTP_RANGE" ], "bytes=%d-" );
	$start = $startArray[0];

	// Generate browser class for sending headers
	$browser = new Browser();
	header("Accept-Ranges: bytes" );
	header("Content-Length: " . $song->size);

	// Prevent the script from timing out
	set_time_limit(0);			
	
	if ($ds) { 
		$ds = $user->prefs['sample_rate'];          
		$dsratio = $ds/$song->bitrate*1024;
		$browser->downloadHeaders($song_name, $song->mime, false,$dsratio*$song->size);

		/* Get Offset */
		$offset = ( $start*$song->time )/( $dsratio*$song->size );
		$offsetmm = floor($offset/60);
		$offsetss = floor($offset-$offsetmm*60);
		$offset   = sprintf("%02d.%02d",$offsetmm,$offsetss);
	
		/* Get EOF */
		$eofmm	= floor($song->time/60);
		$eofss	= floor($song->time-$eofmm*60);
		$eof	= sprintf("%02d.%02d",$eofmm,$eofss);
		
		/* Replace Variables */
		$downsample_command = conf('downsample_cmd');
		$downsample_command = str_replace("%FILE%",$song->file,$downsample_command);
		$downsample_command = str_replace("%OFFSET%",$offset,$downsample_command);
		$downsample_command = str_replace("%EOF%",$eof,$downsample_command);
		$downsample_command = str_replace("%SAMPLE%",$ds,$downsample_command);
		
		// If we are debugging log this event
		if (conf('debug')) { 
			$message = "Exec: $downsample_command";
			log_event($user->username,'downsample',$message);
		} // if debug	

		$fp = @popen($downsample_command, 'r');
	} // if downsampling

	elseif ($start) {
		$browser->downloadHeaders($song_name, $song->mime, false, $song->size);
		fseek( $fp, $start );
		$range = $start ."-". $song->size . "/" . $song->size;
		header("HTTP/1.1 206 Partial Content");
		header("Content-Range: bytes=$range");
	}
	/* Last but not least pump em out */
	else {
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

        if ($bytesStreamed > $minBytesStreamed) {
                $user->update_stats($song_id);
        } 

	// If the played flag isn't set, set it
	if (!$song->played) { 
		$sql = "UPDATE song SET played='1' WHERE id='$song->id'";
		$db_results = mysql_query($sql, $dbh);
	}

	// Remove the song from the now_playing table
	$sql = "DELETE FROM now_playing WHERE id = '$lastid'";
	$db_result = mysql_query($sql, $dbh);

	/* Clean up any open ends */
	if ($ds) { 
		@pclose($fp);
	} 
	else { 
		@fclose($fp);
	}

}

?>
