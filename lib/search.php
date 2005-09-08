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


/** 
 * run_search
 * this function actually runs the search, and returns an array of the results. Unlike the previous
 * function it does not do the display work its self. 
 * @package Search
 * @catagory Search
 */
function run_search($data) { 

	/* Create an array of the object we need to search on */
	foreach ($data['search_object'] as $type) { 
		/* generate the full name of the textbox */
		$fullname = $type . "_string";
		$search[$type] = sql_escape($data[$fullname]);
	} // end foreach

	/* Figure out if they want a AND based search or a OR based search */
	switch($_REQUEST['method']) { 
		case 'fuzzy':
			$method = 'OR';
		break;
		default:
			$method = 'AND';
		break;
	} // end switch on method
	
	/* Switch, and run the correct function */
	switch($_REQUEST['object_type']) { 
		case 'artist':
		case 'album':
		case 'genre':
		case 'song':
			$function_name = 'search_' . $_REQUEST['object_type'];
			if (function_exists($function_name)) { 
				$results = call_user_func($function_name,$search,$method);
				return $results;
			}
		default:
			$results = search_song($search,$method);
			return $results;
		break;
	} // end switch 

	return false;

} // run_search

/** 
 * search_song
 * This function deals specificly with returning song object for the run_search
 * function, it assumes that our root table is songs
 * @package Search
 * @catagory Search
 */
function search_song($data,$method) { 

	/* Generate BASE SQL */
	$base_sql 	= "SELECT song.id FROM song";
	$where_sql 	= '';
	$table_sql	= ',';

	foreach ($data as $type=>$value) { 
		
		switch ($type) { 
			case 'title':
				$where_sql .= " song.title LIKE '%$value%' $method";
			break;
			case 'album':
				$where_sql .= " ( song.album=album.id AND album.name LIKE '%$value%' ) $method";
				$table_sql .= "album,";
			break;
			case 'artist':
				$where_sql .= " ( song.artist=artist.id AND artist.name LIKE '%$value%' ) $method";
				$table_sql .= "artist,";
			break;
			case 'genre':
				$where_sql .= " ( song.genre=genre.id AND genre.name LIKE '%$value%' ) $method";
				$table_sql .= "genre,";
			break;
			case 'year':
				$where_sql .= " song.year LIKE '%$value%' $method";
			break;
			case 'filename':
				$where_sql .= " song.file LIKE '%$value%' $method";
			break;
			case 'played':
				/* This is a 0/1 value so bool it */
				$value = settype($value, "bool");
				$where_sql .= " song.played = '$value' $method";
			break;
			case 'minbitrate':
				$value = intval($value);
				$where_sql .= " song.bitrate >= '$value' $method";
			break;
			default:
				// Notzing!
			break;
		} // end switch on type
		

	} // foreach data

	/* Trim off the extra $method's and ,'s then combine the sucka! */
	$table_sql = rtrim($table_sql,',');
	$where_sql = rtrim($where_sql,$method);

	$sql = $base_sql . $table_sql . " WHERE" . $where_sql;
	$db_results = mysql_query($sql, dbh());
	
	while ($r = mysql_fetch_assoc($db_results)) { 
		$results[] = new Song($r['id']);
	}

	return $results;

} // search_songs


/** 
 * show_search
 * This shows the results of a search, it takes the input from a run_search function call
 * @package Search
 * @catagory Display
 */
function show_search($type,$results) { 

	/* Display based on the type of object we are trying to view */
	switch ($type) { 
		case 'artist':
		
		break;
		case 'album':
		
		break;
		case 'genre':
		
		break;
		case 'song':
		default:
			show_songs($results);
		break;
	} // end type switch

} // show_search

?>
