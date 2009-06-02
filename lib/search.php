<?php
/*

 Copyright (c) Ampache.org
 All rights reserved.

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
			$search[$true_name] = Dba::escape($value);
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

	return array();

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
	$table_sql	= '';
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
                                $ii = 0;
                                foreach($value_words as $word)
                                {
                                    if($ii++ > 0)
                                        $where_sql .= " AND ";
                                    $where_sql .= "(song.title LIKE '%$word%' OR album2.name LIKE '%$word%' OR artist2.name LIKE '%$word%' OR
                                                    song.year LIKE '%$word%' OR song.file LIKE '%$word%' OR tag2.name LIKE '%$word%') ";
                                }
                                $where_sql .= " ) $operator";
                                $table_sql .= " LEFT JOIN `album` as `album2` ON `song`.`album`=`album2`.`id`"; 
				$table_sql .= " LEFT JOIN `artist` AS `artist2` ON `song`.`artist`=`artist2`.`id`"; 
				$table_sql .= " LEFT JOIN `tag_map` ON `song`.`id`=`tag_map`.`object_id` AND `tag_map`.`object_type`='song'"; 
				$table_sql .= " LEFT JOIN `tag` AS `tag2` ON `tag_map`.`tag_id`=`tag2`.`id`"; 
                        break;
			case 'title':
				$where_sql .= " song.title $value_string $operator";
			break;
			case 'album':
				$where_sql .= " album.name $value_string $operator";
				$table_sql .= " LEFT JOIN `album` ON `song`.`album`=`album`.`id`";
			break;
			case 'artist':
				$where_sql .= " artist.name $value_string $operator";
				$table_sql .= " LEFT JOIN `artist` ON `song`.`artist`=`artist`.`id` ";
			break;
			case 'year':
				if (empty($data["year2"]) && is_numeric($data["year"])) {
					$where_sql .= " song.year $value_string $operator";
				}
				elseif (!empty($data["year"]) && is_numeric($data["year"]) && !empty($data["year2"]) && is_numeric($data["year2"])) {
					$where_sql .= " (song.year BETWEEN ".$data["year"]." AND ".$data["year2"].") $operator";
				}
			break;
			case 'time': 
				if (!empty($data['time2'])) { 
					$where_sql .= " `song`.`time` <= " . Dba::escape(intval($data['time2'])*60) . " $operator"; 
				}
				if (!empty($data['time'])) { 
					$where_sql .= " `song`.`time` >= " . Dba::escape(intval($data['time'])*60) . " $operator"; 
				} 
			break; 
			case 'filename':
				$where_sql .= " song.file $value_string $operator";
			break;
			case 'comment':
				$table_sql .= ' INNER JOIN `song_data` ON `song`.`id`=`song_data`.`song_id`';
				$where_sql .= " `song_data`.`comment` $value_string $operator";
			break;
			case 'played':
				/* This is a 0/1 value so bool it */
				$value = make_bool($value);
				$where_sql .= " `song`.`played` = '$value' $operator";
			break;
			case 'minbitrate':
				$value = intval($value);
				$where_sql .= " song.bitrate >= ('$value'*1000) $operator";
			break;
			case 'rating':
				$value = intval($value);

				// This is a little more complext, pull a list of IDs that have this average rating
				$rating_sql = "SELECT `object_id`,AVG(`rating`.`rating`) AS avgrating FROM `rating` " . 
						"WHERE `object_type`='song' GROUP BY `object_id`"; 
				$db_results = Dba::read($rating_sql); 

				// Fill it with one value to prevent sql error on no results
				$where_sql .= " `song`.`id` IN (";

				$ids = array('0'); 

				while ($row = Dba::fetch_assoc($db_results)) { 
					if ($row['avgrating'] < $value) { continue; } 
					$ids[] = $row['object_id']; 
				} 

				$where_sql .= implode(',',$ids) . ') ' . $operator; 
			break;
			case 'tag': 

				// Fill it with one value to prevent sql error on no results
				$ids = array('0'); 

				$tag_sql = "SELECT `object_id` FROM `tag` LEFT JOIN `tag_map` ON `tag`.`id`=`tag_map`.`tag_id` " . 
						"WHERE `tag_map`.`object_type`='song' AND `tag`.`name` $value_string "; 
				$db_results = Dba::read($tag_sql); 

				while ($row = Dba::fetch_assoc($db_results)) { 
					$ids[] = $row['object_id']; 
				} 

				$where_sql  = " `song`.`id` IN (" . implode(',',$ids) . ") $operator"; 

			break; 				
			default:
				// Notzing!
			break;
		} // end switch on type
		

	} // foreach data

	/* Trim off the extra $method's and ,'s then combine the sucka! */
	$where_sql = rtrim($where_sql,$operator);
	$group_sql = rtrim($group_sql,',');
	$select_sql = rtrim($select_sql,',');

	if ($group_sql == ' GROUP BY') { $group_sql = ''; } 
	
	$base_sql 	= "SELECT DISTINCT(song.id) $select_sql FROM `song`";

	$sql = $base_sql . $table_sql . " WHERE (" . $where_sql . ")" . $group_sql . $limit_sql;
	
	/**
	 * Because we might need this for Dynamic Playlist Action 
	 * but we don't trust users to provide this store it in the
	 * session where they can't get to it!
	 */
	$_SESSION['userdata']['stored_search'] = $sql;

	$db_results = Dba::read($sql);

	$results = array(); 
	
	while ($row = Dba::fetch_assoc($db_results)) { 
		$results[] = $row['id'];
	}

	return $results;

} // search_songs


?>
