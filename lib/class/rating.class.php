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

*/

/**
 * Rating class
 * This is an amalgamation(sp?) of code from SoundOfEmotion
 * to track ratings for songs, albums and artists. 
*/
class Rating extends database_object {

	/* Provided vars */
	var $id; 	// The ID of the object who's ratings we want to pull
	var $type; 	// The type of object we want

	/* Generated vars */
	var $rating;	// The average rating as set by all users
	var $preciserating;  // Rating rounded to 1 decimal

	/**
	 * Constructor
	 * This is run every time a new object is created, it requires
	 * the id and type of object that we need to pull the raiting for
	 */
	public function __construct($id,$type) { 

		$this->id 	= intval($id);
		$this->type 	= Dba::escape($type);

		// Check for the users rating
		if ($rating = $this->get_user($GLOBALS['user']->id)) { 
			$this->rating = $rating;
			$this->preciserating = $rating;
		} 
		else { 
			$this->get_average();
		}
	
		return true; 

	} // Constructor

	/**
 	 * build_cache
	 * This attempts to get everything we'll need for this page load in a single query, saving
	 * the connection overhead
	 * //FIXME: Improve logic so that misses get cached as average
	 */
	public static function build_cache($type, $ids) {
		
		if (!is_array($ids) OR !count($ids)) { return false; }

		$user_id = Dba::escape($GLOBALS['user']->id); 

		$idlist = '(' . implode(',', $ids) . ')';
		$sql = "SELECT `rating`, `object_id`,`rating`.`rating` FROM `rating` WHERE `user`='$user_id' AND `object_id` IN $idlist " . 
			"AND `object_type`='$type'";
		$db_results = Dba::read($sql);

		while ($row = Dba::fetch_assoc($db_results)) {
			$user[$row['object_id']] = $row['rating']; 
		}
		
		$sql = "SELECT `rating`,`object_id` FROM `rating` WHERE `object_id` IN $idlist AND `object_type`='$type'"; 
		$db_results = Dba::read($sql); 
		
		while ($row = Dba::fetch_assoc($db_results)) { 
			$rating[$row['object_id']]['rating'] += $row['rating']; 
			$rating[$row['object_id']]['total']++; 
  		} 

		foreach ($ids as $id) { 
			parent::add_to_cache('rating_' . $type . '_user',$id,intval($user[$id])); 

			// Do the bit of math required to store this
			if (!isset($rating[$id])) { 
				$entry = array('average'=>'0','percise'=>'0'); 
			} 
			else { 
				$average = round($rating[$id]['rating']/$rating[$id]['total'],1); 
				$entry = array('average'=>floor($average),'percise'=>$average); 
			} 
			
			parent::add_to_cache('rating_' . $type . '_all',$id,$entry); 
		} 

		return true; 

	} // build_cache

	/**
	 * get_user
	 * Get the user's rating this is based off the currently logged
	 * in user. It returns the value
	 */
	 public function get_user($user_id) {
		
		$id = intval($this->id); 
		
		if (parent::is_cached('rating_' . $this->type . '_user',$id)) { 
			return parent::get_from_cache('rating_' . $this->type . '_user',$id); 
		} 

		$user_id = Dba::escape($user_id); 

		$sql = "SELECT `rating` FROM `rating` WHERE `user`='$user_id' AND `object_id`='$id' AND `object_type`='$this->type'";
		$db_results = Dba::query($sql);
		
		$results = Dba::fetch_assoc($db_results);

		parent::add_to_cache('rating_' . $this->type . '_user',$id,$results['rating']); 
		
		return $results['rating'];

	} // get_user

	/**
	 * get_average
	 * Get the users average rating this is based off the floor'd average
	 * of what everyone has rated this album as. This is shown if there
	 * is no personal rating, and used for random play mojo. It sets 
	 * $this->average_rating and returns the value
	 */
	public function get_average() { 

		$id = intval($this->id); 

		if (parent::is_cached('rating_' . $this->type . '_all',$id)) { 
			$data = parent::get_from_cache('rating_' . $this->type . '_user',$id); 
			$this->rating = $data['rating']; 
			$this->perciserating = $data['percise']; 
			return true; 
		} 

		$sql = "SELECT `rating` FROM `rating` WHERE `object_id`='$id' AND `object_type`='$this->type'";
		$db_results = Dba::query($sql);

		$i = 0;

		while ($r = Dba::fetch_assoc($db_results)) { 
			$i++;
			$total += $r['rating'];
		} // while we're pulling results

		if ($total > 0) { 
			$average = round($total/$i, 1);
		}
		elseif ($i >= '1' AND $total == '0') { 
			$average = -1;
		}
		else { 
			$average = 0;
		}
		
		$this->preciserating = $average;
		$this->rating = floor($average);
		
		return $this->rating;

	} // get_average

	/**
	 * set_rating
	 * This function sets a rating for the current $this object. 
	 * This uses the currently logged in user for the 'user' who is rating
	 * the object. Returns true on success, false on failure
	 */
	public function set_rating($score) { 
		
		$score = Dba::escape($score);

		// If score is -1, then remove rating
		if ($score == '-1') {
			$sql = "DELETE FROM `rating` WHERE `object_id`='$this->id' AND `object_type`='$this->type' " . 
				"AND `user`='" . Dba::escape($GLOBALS['user']->id) . "'";
			$db_results = Dba::query($sql);
			return true;
		}

		/* Check if it exists */
		$sql = "SELECT `id` FROM `rating` WHERE `object_id`='$this->id' AND `object_type`='$this->type' " . 
			"AND `user`='" . Dba::escape($GLOBALS['user']->id) . "'";
		$db_results = Dba::query($sql);

		if ($existing = Dba::fetch_assoc($db_results)) { 
			$sql = "UPDATE `rating` SET `rating`='$score' WHERE `id`='" . $existing['id'] . "'";
			$db_results = Dba::query($sql);
		}
		else { 
			$sql = "INSERT INTO `rating` (`object_id`,`object_type`,`rating`,`user`) VALUES " . 
				" ('$this->id','$this->type','$score','" . $GLOBALS['user']->id . "')";
			$db_results = Dba::query($sql);
		} 

		return true;

	} // set_rating

	/**
	 * show
	 * This takes an id and a type and displays the rating if ratings are enabled. 
	 */
	public static function show ($object_id,$type) { 

		// If there aren't ratings don't return anything
		if (!Config::get('ratings')) { return false; } 

		$rating = new Rating($object_id,$type); 

		require Config::get('prefix') . '/templates/show_object_rating.inc.php'; 

	} // show 

	/**
	 * show_static
	 * This is a static version of the ratings created by Andy90 
	 */
	public static function show_static ($object_id,$type) { 

		// If there aren't ratings don't return anything
		if (!Config::get('ratings')) { return false; } 

		$rating = new Rating($object_id,$type); 

		require Config::get('prefix') . '/templates/show_static_object_rating.inc.php'; 

	} // show_static

} //end rating class
?>
