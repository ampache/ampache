<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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


 This library handles all artist mojo

*/

/*!
	@function get_artists
	@discussion run a search, takes string,field,type and returns an array
		of results of the correct type (song, album, artist)
*/
function get_artists($sql, $action=0) {

	$db_results = mysql_query($sql, dbh());
	
	while ($r = mysql_fetch_array($db_results)) {
		$artist_info = get_artist_info($r['id']);
		if ($action ==='format') { $artist = format_artist($artist_info); }
		else { $artist = $artist_info; }
		$artists[] = $artist;
	} // end while

	return $artists;

} // get_artists

/*!
        @function format_artist
        @discussion this function takes an array of artist
                information and reformats the relevent values
                so they can be displayed in a table for example
                it changes the title into a full link.
*/
function format_artist($artist) {

        $web_path = conf('web_path');
        $artist['name'] = "<a href=\"$web_path/artists.php?action=show&amp;artist=" . $artist['id'] . "\">" . htmlspecialchars($artist['prefix']) . " " . htmlspecialchars($artist['name']) . "</a>";

	return $artist;

} // format_artist

/*!
	@function show_artists
	@discussion takes a match and accounts for the possiblity of a view
		then displays _many_ artists
*/
function show_artists ($match = '') {

        $dbh = dbh();

        $view = new View();
        $view->import_session_view();

        // Check for the view object...
        if ($_REQUEST['keep_view']) {
                $view->initialize();
        }

        // If there isn't a view object we need to create a new one..
        else {

		// Pull in the min object count
		$min_object_count	= conf('min_object_count');
		$min_join	= " LEFT JOIN song ON song.artist=artist.id"; 
		$min_group	= "GROUP BY song.artist HAVING COUNT(song.id) > $min_object_count"; 


                if ( isset($match) && $match != '' ) {
                        $query = "SELECT artist.id,artist.name FROM artist $min_join" .
                                " WHERE artist.name LIKE '$match%' $min_group";
                }
                else {
                        $query = "SELECT artist.id FROM `artist` $min_join $min_group";
                }

                $db_results = mysql_query($query, $dbh);
                $total_items = mysql_num_rows($db_results);
                if ($_REQUEST['match'] === "Show_all") {
                        $offset_limit = 999999;
                }
                else {
                         $offset_limit = $user->prefs['offset_limit'];
                }
                $view = new View($query,'artists.php','name',$total_items,$offset_limit);
		
        } // end if creating view object

        if (is_array($match)) {
                $artists = $match;
                $_SESSION['view_script'] = false;
        }
        $db_results = mysql_query($view->sql, $dbh);

	// Get the artist object
        while ($r = mysql_fetch_assoc($db_results)) {
		$artist = new Artist($r['id']); 	
		$artist->format(); 
		$artists[] = $artist;
        }

        if (count($artists)) {
		/* Ack horrible hack :( */
		$GLOBALS['view'] = $view;
                require conf('prefix') . '/templates/show_artists.inc.php';
        }

} // show_artists




?>
