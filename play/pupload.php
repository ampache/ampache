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

$browser = new Browser();

$dbh = setup_sess_db("song",
                     libglue_param('mysql_host'),
                     libglue_param('mysql_db'),
                     libglue_param('mysql_user'),
                     libglue_param('mysql_pass')
                     );

/* These parameters has better come on the url. */
$song = htmlspecialchars($_REQUEST['song']);
$song_nm = htmlspecialchars($_REQUEST['song']);
$action = htmlspecialchars($_REQUEST['action']);
$uid = htmlspecialchars($_REQUEST['uid']);
$web_path = conf('web_path');


/* If we are in demo mode.. die here */
if (conf('demo_mode')) { 
	exit();
}

/* 
   If they are using access lists let's make sure 
   that they have enough access to play this mojo
*/
if (conf('access_control') == "true") { 

	$access = new Access(0);
	if (!$access->check("25", $_SERVER['REMOTE_ADDR'])) { 
		echo "Error: Access Denied, Invalid Source ADDR"; 
		exit();
	}

} // access_control is enabled

// Get site preferences
$site = new User(0);
$site->get_preferences();


// require a uid and valid song
if ( isset( $uid ) ) {
	// Create the user object if possible
	$user = new User(0,$uid);

	$song = $site->prefs['upload_dir'] . $song;

	if (!file_exists ( $song )) { echo "Error: No Song"; exit; }
	if ($user->access === 'disabled') { echo "Error: User Disabled"; exit; }
	if (!$user->id && !$user->is_xmlrpc()) { echo "Error: No User Found"; exit; }

}
else {
	exit;
}

/* Get file info  */
$audio_info = new Audioinfo();
$results = $audio_info->Info($song);

$order = conf('id3tag_order');

// set the $key to the first found tag style (according to their prefs)
foreach($order as $key) {
	if ($results[$key]) { break; }
}

// Fetch Song Info
$artist_name = addslashes($results[$key]['artist']);
$album_name = addslashes($results[$key]['album']);
$title = addslashes($results[$key]['title']);
$song_time = intval($results['playing_time']);
$size = filesize($song);
preg_match('/\.([A-Za-z0-9]+)$/', $song,$results);

$type = $results[1];

switch ($type) { 
	case "ogg":
		$mime = "application/x-ogg";
		break;
	case "mp3":
	case "mpeg3":
		$mime = "audio/mpeg";
	case "rm":
		$mime = "audio/x-realaudio";
	break;
}


if ( $_REQUEST['action'] == 'm3u' ) {

    if($temp_user->prefs['play_type'] == 'local_play') {
		// Play the song locally using local play configuration
		$song_name = $artist . " - " . $title . "." . $type;;
		$sess = $_COOKIE[libglue_param('sess_name')];
		//echo "Song Name: $song_name<BR>\n";
		$url = escapeshellarg("$web_path/play/pupload.php?song=$song_nm&uid=$user->id&sid=$sess");
		$localplay_add = conf('localplay_add');
		$localplay_add = str_replace("%URL%", $url, $localplay_add);
		//echo "Executing: $localplay_add<BR>";
		exec($localplay_add);
		header("Location: $web_path");

    }
    else
    {

		if (conf('force_http_play')) { 
			$http_port = conf('http_port');
			$web_path = preg_replace("/https/","http",$web_path);
			$web_path = preg_replace("/:\d+/",":$http_port",$web_path);
		}

		// Send the client an m3u playlist
		header("Cache-control: public");
		header("Content-Disposition: filename=playlist.m3u");
		header("Content-Type: audio/x-mpegurl;");
		echo "#EXTM3U\n";

		$song_name = $song . " - " . $title . "." . $type;
		$song_name = $artist . " - " . $title . "." . $song->type;;
		echo "#EXTINF:$song_time,$title\n";
		$sess = $_COOKIE[libglue_param('sess_name')];
					if($temp_user->prefs['down-sample'] == 'true')
						$ds = $temp_user->prefs['sample_rate'];
		echo "$web_path/play/pupload.php?song=" . rawurlencode($song_nm) . "&uid=$user->id&sid=$sess";

    }
	exit;
}
else
{
	// Check to see if we should be downsampling
	$user->get_preferences();

	if ($user->prefs['play_type'] == 'downsample') { 
		$ds = $user->prefs['sample_rate']; 
	}

	// make fread binary safe
	set_magic_quotes_runtime(0);

	// don't abort the script if user skips this song because we need to update now_playing
	ignore_user_abort(TRUE);

	// Build the Song Name
	$song_name = $artist_name . " - " . $title . "." . $type;

	// Send headers
	$browser->downloadHeaders($song_name, $mime, false, $size );
	header("Accept-Ranges: bytes" );

	// Send file, possible at a byte offset
	$fp = fopen($song, 'r');

	$startArray = sscanf( $_SERVER[ "HTTP_RANGE" ], "bytes=%d-" );
	$start = $startArray[0];

	// Prevent the script from timing out
	set_time_limit(0);			
	
	if ($ds) { 
		// FIXME:: $dsratio needs to be set
		$ds = $user->prefs['sample_rate'];                 //ds hack
		$dsratio = $ds/$song->bitrate*1000; //resample hack
		$browser->downloadHeaders($song_name, $mime, false,$dsratio*$size);
		$offset = ( $start*$song_time )/( $dsratio*$size );
		$offsetmm = floor($offset/60);
		$offsetss = floor($offset-$offsetmm*60);
		$offset   = sprintf("%02d.%02d",$offsetmm,$offsetss);
		$cmd = "mp3splt -qnf \"$song->file\" $offset EOF -o - | lame --mp3input -q 3 -b $ds -S - -";
		if (!$handle = fopen('/tmp/ampache.log', 'a'));
		fwrite($handle,"$offset |");
		fclose($handle);
		$fp = popen($cmd, 'r');
		$close='pclose';
	} // if downsampling

	elseif ($start) {
		fseek( $fp, $start );
		header("Content-Range: bytes=$start-$size/$song");
	}

	while (!feof($fp) && (connection_status() == 0)) {
		print(fread($fp, 8192));
	}

	fclose($fp);

}

?>
