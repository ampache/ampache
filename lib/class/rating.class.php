<?php
/*

 Copyright (c) 2001 - 2006 Ampache.org
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
 * Rating class
 * This is an amalgamation(sp?) of code from SoundOfEmotion
 * to track ratings for songs, albums and artists. 
*/
class Rating {

	/* Provided vars */
	var $id; 	// The ID of the object who's ratings we want to pull
	var $type; 	// The type of object we want

	/* Generated vars */
	var $rating;	// The average rating as set by all users

	/**
	 * Constructor
	 * This is run every time a new object is created, it requires
	 * the id and type of object that we need to pull the raiting for
	 */
	function Rating($id,$type) { 

		$this->id 	= intval($id);
		$this->type 	= sql_escape($type);

		if (intval($id) > 1) { 
			$this->get_average();
		}
		else {
			$this->rating='0';
		}

	} // Rating

	/**
	 * get_user
	 * Get the user's rating this is based off the currently logged
	 * in user. It returns the value
	 */
	function get_user($username) { 

		$username = sql_escape($username);

		$sql = "SELECT rating FROM ratings WHERE user='$username' AND object_id='$this->id' AND object_type='$this->type'";
		$db_results = mysql_query($sql, dbh());
		
		$results = mysql_fetch_assoc($db_results);

		return $results['rating'];

	} // get_user

	/**
	 * get_average
	 * Get the users average rating this is based off the floor'd average
	 * of what everyone has rated this album as. This is shown if there
	 * is no personal rating, and used for random play mojo. It sets 
	 * $this->average_rating and returns the value
	 */
	function get_average() { 

		$sql = "SELECT user_rating as rating FROM ratings WHERE object_id='$this->id' AND object_type='$this->type'";
		$db_results = mysql_query($sql, dbh());

		$i = 0;

		while ($r = mysql_fetch_assoc($db_results)) { 
			$i++;
			$total += $r['rating'];
		} // while we're pulling results

		if ($total > 0) { 
			$average = floor($total/$i);
			$this->rating = $average;
		}
		elseif ($i >= '1' AND $total == '0') { 
			$this->rating = '-1';
		}
		else { 
			$this->rating = '0';
		}
		
		return $average;

	} // get_average

	/**
	 * set_rating
	 * This function sets a rating for the current $this object. 
	 * This uses the currently logged in user for the 'user' who is rating
	 * the object. Returns true on success, false on failure
	 */
	function set_rating($score) { 
		
		$score = sql_escape($score);

		/* Check if it exists */
		$sql = "SELECT id FROM ratings WHERE object_id='$this->id' AND object_type='$this->type' AND `user`='" . sql_escape($GLOBALS['user']->username) . "'";
		$db_results = mysql_query($sql, dbh());

		if ($existing = mysql_fetch_assoc($db_results)) { 
			$sql = "UPDATE ratings SET user_rating='$score' WHERE id='" . $existing['id'] . "'";
			$db_results = mysql_query($sql, dbh());
		}
		else { 
			$sql = "INSERT INTO ratings (`object_id`,`object_type`,`user_rating`,`user`) VALUES " . 
				" ('$this->id','$this->type','$score','" . sql_escape($GLOBALS['user']->username) . "')";
			$db_results = mysql_query($sql, dbh());
		} 

		return true;

	} // set_rating

} //end rating class
?>
