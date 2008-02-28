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
 * show_now_playing
 * shows the now playing template
 */
function show_now_playing() {

	// GC!
	Stream::gc_session(); 
	Stream::gc_now_playing();  

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

?>
