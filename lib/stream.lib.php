<?php
/*

 Copyright 2001 - 2007 Ampache.org
 All Rights Reserved

 This program is free software; you can redistribute it and/or
 modify it under the terms of the GNU General Public License v2
 as published by the Free Software Foundation.

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
