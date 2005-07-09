<?php
/*

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
	@header Song Library
 	@discussion This library handles song related functions.... woohoo!
		This library is defunt, please try use the song class if possible

*/

/*!
	@function get_songs
	@discussion pass a sql statement, and it gets full song info and returns
		an array of the goods.. can be set to format them as well
*/
function get_songs($sql, $action=0) {

	$db_results = mysql_query($sql, dbh());
	while ($r = mysql_fetch_array($db_results)) {
//		$song_info = get_songinfo($r['id']);
//		if ($action === 'format') { $song = format_song($song_info); }
//		else { $song = $song_info; }
		$results[] = $r['id'];
	}

	return $results;


} // get_albums

/*!
	@function format_song
	@discussion takes a song array and makes it html friendly
*/
function format_song($song) {

	return $song;

} // format_song

/**
 * get_popular_songs
 * This returns the current popular songs
 * @package Stream
 * @catagory Get
 */
function get_popular_songs( $threshold, $type, $user_id = '' ) {

        $dbh = dbh();

        if ( $type == 'your' ) {
                $sql = "SELECT object_id FROM object_count" .
                        " WHERE object_type = 'song'" .
                        " AND userid = '$user_id'" .
                        " ORDER BY count DESC LIMIT $threshold";
        }
        else {
                $sql = "SELECT object_id FROM object_count" .
                        " WHERE object_type = 'song'" .
                        " ORDER BY count DESC LIMIT $threshold";
        }
        
        $db_result = mysql_query($sql, $dbh);
        $songs = array();
        
        while ( $id = mysql_fetch_array($db_result) ) {
                $songs[] = $id[0];
        }
        
        return $songs;  

} // get_popular_songs()



?>
