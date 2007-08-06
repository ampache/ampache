<?php
/*

 Copyright (c) 2001 - 2007 Ampache.org
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

/**
 * Stats
 * this class handles the object_count
 * Stuff, before this was done in the user class
 * but that's not good, all done through here. 
 */
class Stats {

	/* Base vars */
	var $id; 
	var $object_type; 
	var $object_id;
	var $date;
	var $user;


	/**
 	 * Constructor
	 * This doesn't do anything currently
	 */
	public function __construct() { 

		return true;

	} // Constructor

	/**
 	 * insert
	 * This inserts a new record for the specified object
	 * with the specified information, amazing! 
	 */
	public static function insert($type,$oid,$user) { 

		$type 	= self::validate_type($type);
		$oid	= Dba::escape($oid);
		$user	= Dba::escape($user);	
		$date	= time();

		$sql = "INSERT INTO `object_count` (`object_type`,`object_id`,`date`,`user`) " . 
			" VALUES ('$type','$oid','$date','$user')";
		$db_results = Dba::query($sql);

		if (!$db_results) { 
			debug_event('statistics','Unabled to insert statistics:' . $sql,'3');
		}	

	} // insert

	/**
 	 * get_top
	 * This returns the top X for type Y from the
	 * last conf('stats_threshold') days
	 */
	public static function get_top($count,$type,$threshold = '') { 

		/* If they don't pass one, then use the preference */
		if (!$threshold) { 
			$threshold = Config::get('stats_threshold');
		}

		$count	= intval($count);
		$type	= self::validate_type($type);
		$date	= time() - (86400*$threshold);
		
		/* Select Top objects counting by # of rows */
		$sql = "SELECT object_id,COUNT(id) AS `count` FROM object_count" . 
			" WHERE object_type='$type' AND date >= '$date'" .
			" GROUP BY object_id ORDER BY `count` DESC LIMIT $count";
		$db_results = Dba::query($sql);

		$results = array();

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r;
		}

		return $results;

	} // get_top

	/**
 	 * get_user
	 * This gets all stats for atype based on user with thresholds and all
	 * If full is passed, doesn't limit based on date 
	 */
	public static function get_user($count,$type,$user,$full='') { 

		$count 	= intval($count);
		$type	= self::validate_type($type);
		$user	= Dba::escape($user);
	
		/* If full then don't limit on date */	
		if ($full) { 
			$date = '0';
		}
		else { 
			$date = time() - (86400*Config::get('stats_threshold'));
		}

		/* Select Objects based on user */
		//FIXME:: Requires table scan, look at improving 
		$sql = "SELECT object_id,COUNT(id) AS `count` FROM object_count" . 
			" WHERE object_type='$type' AND date >= '$date' AND user = '$user'" . 
			" GROUP BY object_id ORDER BY `count` DESC LIMIT $count";
		$db_results = Dba::query($sql);
		
		$results = array();

		while ($r = Dba::fetch_assoc($db_results)) { 
			$results[] = $r;
		} 

		return $results;

	} // get_user

	/**
	 * validate_type
	 * This function takes a type and returns only those
	 * which are allowed, ensures good data gets put into the db
	 */
	public static function validate_type($type) { 

		switch ($type) { 
			case 'artist':
				return 'artist';
			break;
			case 'album':
				return 'album';
			break;
			case 'genre':
				return 'genre';
			break;
			case 'song':
			default:
				return 'song';
			break;
		} // end switch

	} // validate_type

	/**
	 * get_newest
	 * This returns an array of the newest artists/albums/whatever
	 * in this ampache instance
	 */
	public static function get_newest($type,$limit='') { 

		if (!$limit) { $limit = Config::get('popular_threshold'); }
	
		$type = self::validate_type($type); 
		$object_name = ucfirst($type); 

		$sql = "SELECT DISTINCT($type) FROM `song` ORDER BY `addition_time` DESC " . 
			"LIMIT $limit"; 
		$db_results = Dba::query($sql); 

		while ($r = Dba::fetch_row($db_results)) { 
			$object = new $object_name($r['0']); 
			$items[] = $object; 
		} // end while results

		return $items; 

	} // get_newest

} // Stats class
?>
