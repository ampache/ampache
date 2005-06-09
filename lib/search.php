<?php
/*

 Copyright (c) 2004 ampache.org
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
 
 This library handles all the searching!

*/

/*!
	@function run_search
	@discussion run a search, takes string,field,type and returns an array
		of results of the correct type (song, album, artist)
*/
function run_search($string,$field,$type) {

	// Clear this so it doesn't try any fanzy view mojo on us	
	unset($_SESSION['view_script']);

	// Escape input string
	$string = sql_escape($string);

	// Switch on the field --> type and setup sql statement
	switch ($field === 0 ? '': $field) {
		case 'artist':
			if ($type === 'fuzzy') {
				$sql = "SELECT id FROM artist WHERE name LIKE '%$string%'";
			}
			else {
				$sql = "SELECT id FROM artist WHERE name ='$string'";
			}
			$artists = get_artists($sql, 'format');
			if ($artists) {
				show_artists($artists);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

		case 'album':
			if ($type === 'fuzzy') {
				$sql = "SELECT id FROM album WHERE name LIKE '%$string%'";
			}
			else {
				$sql = "SELECT id FROM album WHERE name='$string'";
			}
			$albums = get_albums($sql);
			if (count($albums)) {
				show_albums($albums);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

		case 'song_title':
			if ($type === 'fuzzy') {
				$sql = "SELECT id FROM song WHERE title LIKE '%$string%'";
			}
			else {
				$sql = "SELECT id FROM song WHERE title = '$string'";
			}
			$song_ids = get_songs($sql, 'format');
			if ($song_ids) {
				show_songs($song_ids);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

		case 'song_genre':
			if ($type === 'fuzzy') {
				$sql = "SELECT song.id FROM song,genre WHERE song.genre=genre.id AND genre.name LIKE '%$string%'";
			}
			else {
				$sql = "SELECT song.id FROM song,genre WHERE song.genre=genre.id AND genre.name='$string'";
			}
			$song_ids = get_songs($sql, 'format');
			if ($song_ids) {
				show_songs($song_ids);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

		case 'song_year':
			if ($type === 'fuzzy') {
				$sql = "SELECT song.id FROM song WHERE song.year LIKE '%$string%'";
			}
			else {
				$sql = "SELECT song.id FROM song WHERE song.year='$string'";
			}
			$song_ids = get_songs($sql, 'format');
			if ($song_ids) {
				show_songs($song_ids);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

		case 'song_length':	
		case 'song_bitrate':
			if ($type === 'fuzzy') {
				$sql = "SELECT song.id FROM song WHERE song.bitrate LIKE '%$string%'";
			}
			else {
				$sql = "SELECT song.id FROM song WHERE song.bitrate='$string'";
			}
			$song_ids = get_songs($sql, 'format');
			if ($song_ids) {
				show_songs($song_ids);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

		case 'song_min_bitrate':
			$string = $string * 1000;
			$sql = "SELECT song.id FROM song WHERE song.bitrate >= '$string'"; 
			$song_ids = get_songs($sql, 'format');
			if ($song_ids) {
				show_songs($song_ids);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

		case 'song_comment':
			if ($type === 'fuzzy') {
				$sql = "SELECT song.id FROM song WHERE song.comment LIKE '%$string%'";
			}
			else {
				$sql = "SELECT song.id FROM song WHERE song.comment='$string'";
			}
			$song_ids = get_songs($sql, 'format');
			if ($song_ids) {
				show_songs($song_ids);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

		case 'song_filename':
			if ($type === 'fuzzy') {
				$sql = "SELECT song.id FROM song WHERE song.file LIKE '%$string%'";
			}
			else {
				$sql = "SELECT song.id FROM song WHERE song.file='$string'";
			}
			$song_ids = get_songs($sql, 'format');
			if ($song_ids) {
				show_songs($song_ids);
			}
			else {
				echo "<div class=\"error\" align=\"center\">" . _("No Results Found") . "</div>";
			}
			break;

	} // end switch

} // run_search

?>
