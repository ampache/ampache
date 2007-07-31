<?php
/*

 Copyright 2001 - 2007 Ampache.org
 All Rights Reserved

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

/** 
 * gc_now_playing
 * this is a garbage collection function for now playing this is called every time something
 * is streamed
 * @package General
 * @catagory Now Playing
 */
function gc_now_playing() { 

	// Delete expired songs
        $sql = "DELETE FROM `now_playing` WHERE `expire` < '$time'";
        $db_result = Dba::query($sql);

	// Remove any now playing entries for session_streams that have been GC'd
	$sql = "DELETE FROM `now_playing` USING `now_playing` " . 
		"LEFT JOIN `session_stream` ON `session_stream`.`id`=`now_playing`.`id` " . 
		"WHERE `session_stream`.`id` IS NULL"; 
	$db_results = Dba::query($sql); 

} // gc_now_playing

/**
 * insert_now_playing
 * This function takes care of inserting the now playing data
 * we use this function because we need to do thing differently
 * depending upon which play is actually streaming
 */
function insert_now_playing($song_id,$uid,$song_length,$sid) {

        $time = time()+$song_length;
	$session_id = Dba::escape($sid); 

	// Do a replace into ensuring that this client always only has a single row
        $sql = "REPLACE INTO `now_playing` (`id`,`song_id`, `user`, `expire`)" .
                " VALUES ('$session_id','$song_id', '$uid', '$time')";
        
	$db_result = Dba::query($sql);

} // insert_now_playing

/**
 * clear_now_playing
 * There really isn't anywhere else for this function, shouldn't have deleted it in the first
 * place
 */
function clear_now_playing() { 

	$sql = "TRUNCATE `now_playing`"; 
	$db_results = Dba::query($sql); 

	return true; 

} // clear_now_playing

/**
 * show_now_playing
 * shows the now playing template
 */
function show_now_playing() {

	// GC!
	Stream::gc_session(); 
	gc_now_playing();  

        $web_path = Config::get('web_path');
        $results = get_now_playing();
        require Config::get('prefix') . '/templates/show_now_playing.inc.php';

} // show_now_playing

/**
 * get_now_playing
 * gets the now playing information
 */
function get_now_playing($filter='') {

        $sql = "SELECT `session_stream`.`agent`,`now_playing`.`song_id`,`now_playing`.`user` FROM `now_playing` " . 
		"LEFT JOIN `session_stream` ON `session_stream`.`id`=`now_playing`.`id` " . 
		"ORDER BY `now_playing`.`expire` DESC";
        $db_results = Dba::query($sql);

        $results = array();

        /* While we've got stuff playing */
        while ($r = Dba::fetch_assoc($db_results)) {
                $song = new Song($r['song_id']);
                $song->format();
                $np_user = new User($r['user']);
                $results[] = array('song'=>$song,'user'=>$np_user,'agent'=>$r['agent']);
        } // end while

        return $results;

} // get_now_playing


/**
 * check_lock_songs
 * This checks to see if the song is already playing, if it is then it prevents the user
 * from streaming it
 */
function check_lock_songs($song_id) { 

	$sql = "SELECT `song_id` FROM `now_playing` " . 
		"WHERE `song_id` = '$song_id'";
	$db_results = Dba::query($sql);

	if (Dba::num_rows($db_results)) { 
		debug_event('lock_songs','Song Already Playing, skipping...','5'); 
		return false;
	}

	return true;

} // check_lock_songs

/**
 * start_downsample
 * This is a rather complext function that starts the downsampling of a song and returns the 
 * opened file handled
 */
function start_downsample($song,$now_playing_id=0,$song_name=0) { 

	/* Check to see if bitrates are set if so let's go ahead and optomize! */
	$max_bitrate = Config::get('max_bit_rate');
	$min_bitrate = Config::get('min_bit_rate');
	$time = time();
	$user_sample_rate = $GLOBALS['user']->prefs['sample_rate'];
	$browser = new Browser();
	
	if (!$song_name) { 
		$song_name = $song->f_artist_full . " - " . $song->title . "." . $song->type;
	}

	if ($max_bitrate > 1 AND $min_bitrate < $max_bitrate) {   
		$last_seen_time = $time - 1200; //20 min.

		$sql = "SELECT COUNT(*) FROM now_playing, user_preference, preferences " . 
			"WHERE preferences.name = 'play_type' AND user_preference.preference = preferences.id " . 
			"AND now_playing.user = user_preference.user AND user_preference.value='downsample'";
		$db_results = Dba::query($sql);
		$results = Dba::fetch_row($db_results);

		// Current number of active streams (current is already in now playing)
		$active_streams = $results[0];

		/* If only one user, they'll get all available.  Otherwise split up equally. */
		$sample_rate = floor($max_bitrate/$active_streams);

		/* If min_bitrate is set, then we'll exit if the bandwidth would need to be split up smaller than the min. */
		if ($min_bitrate > 1 AND ($max_bitrate/$active_streams) < $min_bitrate) {
               
			/* Log the failure */
			debug_event('downsample',"Error: Max bandwidith already allocated. $active_streams Active Streams",'2'); 
			
			/* Toast the now playing entry, then tell em to try again later */
			delete_now_playing($now_playing_id);
			echo "Maximum bandwidth already allocated.  Try again later.";
        	        exit();

		}
		else {
               		$sample_rate = floor($max_bitrate/$active_streams);
		} // end else
     
		// Never go over the users sample rate 
		if ($sample_rate > $user_sample_rate) { $sample_rate = $user_sample_rate; }	

		debug_event('downsample',"Downsampled: $active_streams current active streams, downsampling to $sample_rate",'2');

	} // end if we've got bitrates

	else { 
		$sample_rate = $user_sample_rate;
	}

	/* Validate the bitrate */
	$sample_rate = validate_bitrate($sample_rate);
 
	/* Never Upsample a song */
	if (($sample_rate*1000) > $song->bitrate) {
		$sample_rate = validate_bitrate($song->bitrate)/1000;
		$sample_ratio = '1';
	}
	else { 
		/* Set the Sample Ratio */
		$sample_ratio = $sample_rate/($song->bitrate/1000);
	}


	header("Content-Length: " . intval($sample_ratio*$song->size));
        $browser->downloadHeaders($song_name, $song->mime, false,$sample_ratio*$song->size);

        /* Get Offset */
	$offset = ( $start*$song->time )/( $sample_ratio*$song->size );
        $offsetmm = floor($offset/60);
        $offsetss = floor($offset-$offsetmm*60);
        $offset   = sprintf("%02d.%02d",$offsetmm,$offsetss);

        /* Get EOF */
	$eofmm  = floor($song->time/60);
        $eofss  = floor($song->time-$eofmm*60);
        $eof    = sprintf("%02d.%02d",$eofmm,$eofss);

	$song_file = escapeshellarg($song->file);

        /* Replace Variables */
        $downsample_command = Config::get($song->stream_cmd());
        $downsample_command = str_replace("%FILE%",$song_file,$downsample_command);
        $downsample_command = str_replace("%OFFSET%",$offset,$downsample_command);
        $downsample_command = str_replace("%EOF%",$eof,$downsample_command);
        $downsample_command = str_replace("%SAMPLE%",$sample_rate,$downsample_command);

        // If we are debugging log this event
	$message = "Start Downsample: $downsample_command";
        debug_event('downsample',$message,'3');

	$fp = @popen($downsample_command, 'rb');

	/* We need more than just the handle here */
	$return_array['handle'] = $fp;
	$return_array['size']	= $sample_ratio*$song->size;

	return ($return_array);

} // start_downsample

/** 
 * validate_bitrate
 * this function takes a bitrate and returns a valid one
 * @package Stream
 * @catagory Downsample
 */
function validate_bitrate($bitrate) { 


	// Setup an array of valid bitrates for Lame (yea yea, others might be different :P)
        $valid_rate = array('32','40','56','64','80','96','112','128','160','192','224','256','320');

	/* Round to standard bitrates */
        $sample_rate = 8*(floor($bitrate/8));

	if (in_array($sample_rate,$valid_rate)) { 
		return $sample_rate;
	}

	/* See if it's less than the lowest one */
	if ($sample_rate < $valid_rate['0']) { 
		return $valid_rate['0'];
	}

        /* Check to see if it's over 320 */
        if ($sample_rate > 320) {
		return '320';
        }

        foreach ($valid_rate as $key=>$rate) {
		$next_key = $key+1;
                                
                if ($sample_rate > $rate AND $sample_rate < $valid_rate[$next_key]) {
			return $rate;
		} 
	} // end foreach

} // validate_bitrate

?>
