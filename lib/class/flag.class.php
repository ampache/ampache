<?php
/*

 Copyright 2001 - 2006 Ampache.org
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

*/

/**
 * Flag Class
 * This handles flagging of songs, albums and artists	
 */
class Flag {

	/* DB based variables */
	var $id; 
	var $user;
	var $object_id;
	var $object_type;
	var $comment;
	var $flag;
	var $date;
	var $approved=0;

	/* Generated Values */
	var $name; // Blank
	var $title; // Blank

	/**
	 * Constructor
	 * This takes a flagged.id and then pulls in the information for said flag entry
	 */
	function Flag($flag_id=0) { 

		$this->id = intval($flag_id);

		if (!$this->id) { return false; }

		$info = $this->_get_info();

		$this->user		= $info['user'];
		$this->object_id	= $info['object_id'];
		$this->object_type	= $info['object_type'];
		$this->comment		= $info['comment'];
		$this->flag		= $info['flag'];
		$this->date		= $info['date'];
		$this->approved		= $info['approved'];
		$f_user 		= $this->format_user();
		$this->f_user_fullname  = $f_user['fullname'];
		$this->f_user_username  = $f_user['username'];
		return true;

	} // flag

	/**
	 * _get_info
	 * Private function for getting the information for this object from the database 
	 */
	function _get_info() { 

		$id = sql_escape($this->id);

		$sql = "SELECT * FROM flagged WHERE id='$id'";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_assoc($db_results);
		
		return $results;

	} // _get_info

	/**
	 * get_recent
	 * This returns the id's of the most recently flagged songs, it takes an int
	 * as an argument which is the count of the object you want to return
	 */
	function get_recent($count=0) { 

		if ($count) { $limit = " LIMIT " . intval($count);  } 

		$results = array();

		$sql = "SELECT id FROM flagged ORDER BY date " . $limit;
		$db_results = mysql_query($sql, dbh());

		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}
		
		return $results;

	} // get_recent

	/**
	 * get_total
	 * Returns the total number of flagged objects
	 */
	function get_total() { 

		$sql = "SELECT COUNT(id) FROM flagged";
		$db_results = mysql_query($sql, dbh());

		$results = mysql_fetch_row($db_results);

		return $results['0'];

	} // get_total

	/**
	 * get_flagged
	 * This returns an array of ids of flagged songs if no limit is passed
	 * it gets everything
	 */
	function get_flagged($count=0) { 

		if ($count) { $limit_clause = "LIMIT " . intval($count); } 
		
		$sql = "SELECT id FROM flagged ORDER BY id $limit_clause";
		$db_results = mysql_query($sql, dbh());

		/* Default it to an array */
		$results = array();

		/* While the query */
		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_flagged

	/**
	 * get_approved
	 * This returns an array of approved flagged songs
	 */
	function get_approved() { 

		$sql = "SELECT id FROM flagged WHERE approved='1'";
		$db_results = mysql_query($sql,dbh()); 


		/* Default the results array */
		$results = array(); 

		/* While it */
		while ($r = mysql_fetch_assoc($db_results)) { 
			$results[] = $r['id'];
		}

		return $results;

	} // get_approved

	/**
	 * add
	 * This adds a flag entry for an item, it takes an id, a type, the flag type
	 * and a comment and then inserts the mofo
	 */
	function add($id,$type,$flag,$comment) { 
	
		$id 		= sql_escape($id);
		$type		= sql_escape($type);
		$flag		= sql_escape($flag);
		$user		= sql_escape($GLOBALS['user']->id);
		$comment	= sql_escape($comment);
		$time		= time();
		$approved	= '0';

		/* If they are an admin, it's auto approved */
		if ($GLOBALS['user']->has_access('100')) { $approved = '1'; } 

		$sql = "INSERT INTO flagged (`object_id`,`object_type`,`flag`,`comment`,`date`,`approved`,`user`) VALUES " . 
			" ('$id','$type','$flag','$comment','$time','$approved','$user')";
		$db_results = mysql_query($sql, dbh());

		return true;

	} // add

	/**
	 * delete_flag
	 * This deletes the flagged entry and rescans the file to revert to the origional
	 * state, in a perfect world, I could just roll the changes back... not until 3.4
	 */
	function delete_flag() { 

		$sql = "DELETE FROM flagged WHERE id='$this->id'";
		$db_results = mysql_query($sql, dbh());

		return true;

	} // delete_flag

	/**
	 * approve
	 * This approves the current flag object ($this->id) by setting approved to
	 * 1
	 */
	 function approve() { 

		$sql = "UPDATE flagged SET approved='1' WHERE id='$this->id'";
		$db_results = mysql_query($sql, dbh());

		return true;
	
	 } // approve
	 
	/**
	 * format_user
	 * This formats username etc
	 */
	function format_user() {
		
		$sql = "SELECT * FROM user WHERE id = '$this->user'";
		$db_results = mysql_query($sql, dbh());

		$f_user = mysql_fetch_assoc($db_results);
		
		return $f_user;
	 
	} // format_user

	/**
	 * format_name
	 * This function formats and sets the $this->name variable and $this->title 
	 */
	function format_name() { 

		switch ($this->object_type) { 
			case 'song':
				$song = new Song($this->object_id);
				$song->format();
				$name 	= $song->f_title . " - " . $song->f_artist;
				$title	= $song->title . " - " . $song->get_artist_name();
			break;
			default: 
			
			break;
		} // end switch on object type
		
		$this->title = $title; 
		$this->name = $name;
	} // format_name()
	
	/**
	 * print_name
	 * This function formats and prints out a userfriendly name of the flagged
	 * object
	 */
	function print_name() { 

		$this->format_name();
		echo "<span title=\"" . $this->title . "\">" . $this->name . "</span>";

	} // print_name


	/**
	 * print_status
	 * This prints out a userfriendly version of the current status for this flagged
	 * object
	 */
	function print_status() { 

		if ($this->approved) { echo _('Approved'); }
		else { echo _('Pending'); }

	} // print_status

	/**
	 * print_flag
	 * This prints out a userfriendly version of the current flag type
	 */
	function print_flag() { 

		switch ($this->flag) { 
			case 'delete':
				$name = _('Delete');
			break;
			case 'retag':
				$name = _('Re-Tag'); 
			break;
			case 'reencode':
				$name = _('Re-encode');
			break;
			case 'other':
				$name = _('Other'); 
			break;
			default:
				$name = _('Unknown');
			break;
		} // end switch

		echo $name;
		
	} // print_flag

} //end of flag class

?>
