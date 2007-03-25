<?php
/*

 Copyright (c) 2001 - 2006 ampache.org
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
	foreach ($data as $key=>$value) { 
		/* Get the first two chars to check
		 * and see if it's s_
		 */
		$prefix = substr($key,0,2);
		$value = trim($value);
		
		if ($prefix == 's_' AND strlen($value)) { 
			$true_name = substr($key,2,strlen($key));
			$search[$true_name] = sql_escape($value);
		}
		
	} // end foreach
	
	/* Figure out if they want a AND based search or a OR based search */
	switch($_REQUEST['operator']) { 
		case 'or':
			$operator = 'OR';
		break;
		default:
			$operator = 'AND';
		break;
	} // end switch on operator

	/* Figure out what type of method they would like to use, exact or fuzzy */
	switch($_REQUEST['method']) { 
		case 'fuzzy':
			$method = "LIKE '%__%'";
		break;
		default:
			$method = "= '__'";
		break;
	} // end switch on method

	$limit = intval($_REQUEST['limit']);
	
	/* Switch, and run the correct function */
	switch($_REQUEST['object_type']) { 
		case 'artist':
		case 'album':
		case 'genre':
		case 'song':
			$function_name = 'search_' . $_REQUEST['object_type'];
			if (function_exists($function_name)) { 
				$results = call_user_func($function_name,$search,$operator,$method,$limit);
				return $results;
			}
                break;
                default:
			$results = search_song($search,$operator,$method,$limit);
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
function search_song($data,$operator,$method,$limit) { 

	/* Generate BASE SQL */

	$where_sql 	= '';
	$table_sql	= ',';
        $join_sql       = '';
	$group_sql 	= ' GROUP BY';
	$select_sql	= ',';

	if ($limit > 0) { 
		$limit_sql = " LIMIT $limit";
	}
	
	foreach ($data as $type=>$value) { 
	
		/* Create correct Value statement based on method */

		$value_string = str_replace("__",$value,$method);
	
		switch ($type) { 
                        case 'all': /* artist, title, and album, anyway.. */
                                $value_words = explode(' ', $value);
                                $where_sql .= " ( ";
                                $ii == 0;
                                foreach($value_words as $word)
                                {
                                    if($ii++ > 0)
                                        $where_sql .= " AND ";
                                    $where_sql .= "
                                                 ( 
                                                    song.title LIKE '%$word%' OR
                                                    album2.name LIKE '%$word%' OR
                                                    artist2.name LIKE '%$word%' OR
                                                    genre2.name LIKE '%$word%' OR
                                                    song.year LIKE '%$word%' OR
                                                    song.file LIKE '%$word%'
                                                  ) ";
                                }
                                $where_sql .= " ) $operator";
                                $join_sql  .= "song.album=album2.id AND song.artist=artist2.id AND song.genre=genre2.id AND ";
                                $table_sql .= "album as album2,artist as artist2, genre as genre2";
                        break;
			case 'title':
				$where_sql .= " song.title $value_string $operator";
			break;
			case 'album':
				$where_sql .= " album.name $value_string $operator";
                                $join_sql  .= "song.album=album.id AND ";
				$table_sql .= "album,";
			break;
			case 'artist':
				$where_sql .= " artist.name $value_string $operator";
                                $join_sql  .= "song.artist=artist.id AND ";
				$table_sql .= "artist,";
			break;
			case 'genre':
				$where_sql .= " genre.name $value_string $operator";
                                $join_sql  .= "song.genre=genre.id AND ";
				$table_sql .= "genre,";
			break;
			case 'year':
				if (empty($data["year2"]) && is_numeric($data["year"])) {
					$where_sql .= " song.year $value_string $operator";
				}
				elseif (!empty($data["year"]) && is_numeric($data["year"]) && !empty($data["year2"]) && is_numeric($data["year2"])) {
					$where_sql .= " (song.year BETWEEN ".$data["year"]." AND ".$data["year2"].") $operator";
				}
			break;
			case 'filename':
				$where_sql .= " song.file $value_string $operator";
			break;
			case 'comment':
				$join_sql  .= 'song.id=song_ext_data.song_id AND ';
				$table_sql .= 'song_ext_data,';
				$where_sql .= " song_ext_data.comment $value_string $operator";
			break;
			case 'played':
				/* This is a 0/1 value so bool it */
				$value = make_bool($value);
				$where_sql .= " song.played = '$value' $operator";
			break;
			case 'minbitrate':
				$value = intval($value);
				$where_sql .= " song.bitrate >= ('$value'*1000) $operator";
			break;
			case 'rating':
				$value = intval($value);
				$select_sql .= "SUM(ratings.user_rating)/(SELECT COUNT(song.id) FROM song,ratings WHERE ratings.object_id=song.id AND ratings.object_type='song' AND ratings.user_rating >= '$value') AS avgrating,";
				$group_sql .= " ratings.user_rating,";
				$where_sql .= " (ratings.user_rating >= '$value' AND ratings.object_type='song')";
				$table_sql .= "ratings,";
				$join_sql  .= "ratings.object_id=song.id AND";
				$limit_sql .= " ORDER BY avgrating DESC";
			default:
				// Notzing!
			break;
		} // end switch on type
		

	} // foreach data

	/* Trim off the extra $method's and ,'s then combine the sucka! */
	$table_sql = rtrim($table_sql,',');
	$where_sql = rtrim($where_sql,$operator);
	$group_sql = rtrim($group_sql,',');
	$select_sql = rtrim($select_sql,',');

	if ($group_sql == ' GROUP BY') { $group_sql = ''; } 
	
	$base_sql 	= "SELECT DISTINCT(song.id) $select_sql FROM song";

	$sql = $base_sql . $table_sql . " WHERE " . $join_sql . " (" . $where_sql . ")" . $group_sql . $limit_sql;
	
	/**
	 * Because we might need this for Dynamic Playlist Action 
	 * but we don't trust users to provide this store it in the
	 * session where they can't get to it!
	 */
	$_SESSION['userdata']['stored_search'] = $sql;

        //echo "DEBUG: $sql<BR>"; flush();
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
			show_songs($results,0);
		break;
	} // end type switch

} // show_search

?>
